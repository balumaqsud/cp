import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'preview', 'status']
    static values = {
        cloud: String,
        preset: String,
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
        if (!this.cloudValue || !this.presetValue) {
            return
        }

        this.setStatus(this.uploadingValue)

        const body = new FormData()
        body.append('file', file)
        body.append('upload_preset', this.presetValue)

        try {
            const response = await fetch(`https://api.cloudinary.com/v1_1/${this.cloudValue}/image/upload`, {
                method: 'POST',
                body,
            })
            const payload = await response.json()
            if (!response.ok || !payload.secure_url) {
                throw new Error('upload')
            }

            this.inputTarget.value = payload.secure_url
            this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }))
            this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }))
            if (this.hasPreviewTarget) {
                this.previewTarget.src = payload.secure_url
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
