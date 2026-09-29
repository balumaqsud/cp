import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'preview', 'status']
    static values = {
        url: String,
        csrf: String,
        uploading: { type: String, default: 'Uploading…' },
        failed: { type: String, default: 'Upload failed.' },
    }

    dragover(event) {
        event.preventDefault()
    }

    drop(event) {
        event.preventDefault()
        const file = event.dataTransfer?.files?.[0]
        if (file) {
            void this.upload(file)
        }
    }

    pick(event) {
        const file = event.target.files?.[0]
        if (file) {
            void this.upload(file)
        }
    }

    async upload(file) {
        if (!this.urlValue || !this.csrfValue) {
            return
        }

        this.setStatus(this.uploadingValue)

        try {
            const signed = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    contentType: file.type,
                    filename: file.name,
                    _token: this.csrfValue,
                }),
            })
            const payload = await signed.json()
            if (!signed.ok || !payload.uploadUrl || !payload.publicUrl) {
                throw new Error('sign')
            }

            const uploaded = await fetch(payload.uploadUrl, {
                method: 'PUT',
                headers: { 'Content-Type': payload.contentType || file.type },
                body: file,
            })
            if (!uploaded.ok) {
                throw new Error('upload')
            }

            this.inputTarget.value = payload.publicUrl
            this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }))
            this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }))
            if (this.hasPreviewTarget) {
                this.previewTarget.src = payload.publicUrl
                this.previewTarget.classList.remove('d-none')
            }
            this.setStatus('')
        } catch {
            this.setStatus(this.failedValue)
        }
    }

    setStatus(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message
        }
    }
}
