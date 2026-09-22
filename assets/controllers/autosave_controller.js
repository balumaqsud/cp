import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['status', 'tab', 'form']
    static values = {
        interval: { type: Number, default: 8000 },
        unsaved: String,
        saving: String,
        saved: String,
    }

    connect() {
        this.dirty = false
        this.saving = false
        this.onDirty = () => this.markDirty()
        this.formTarget.addEventListener('input', this.onDirty)
        this.formTarget.addEventListener('change', this.onDirty)
        document.addEventListener('input', this.onAssociatedInput)
        document.addEventListener('change', this.onAssociatedInput)
        this.timer = window.setInterval(() => {
            void this.saveIfDirty()
        }, this.intervalValue)
    }

    disconnect() {
        this.formTarget.removeEventListener('input', this.onDirty)
        this.formTarget.removeEventListener('change', this.onDirty)
        document.removeEventListener('input', this.onAssociatedInput)
        document.removeEventListener('change', this.onAssociatedInput)
        window.clearInterval(this.timer)
    }

    switchTab(event) {
        if (this.hasTabTarget) {
            this.tabTarget.value = event.currentTarget.dataset.profileTab ?? 'me'
        }
    }

    onAssociatedInput = (event) => {
        const field = event.target
        if (!(field instanceof HTMLElement)) {
            return
        }

        if (field.getAttribute('form') === this.formTarget.id) {
            this.markDirty()
        }
    }

    markDirty() {
        this.dirty = true
        this.showStatus(this.unsavedValue)
    }

    async saveIfDirty() {
        if (!this.dirty || this.saving || document.hidden || !this.hasFormTarget) {
            return
        }

        this.saving = true
        this.showStatus(this.savingValue)

        const response = await fetch(this.formTarget.action, {
            method: 'POST',
            body: new FormData(this.formTarget),
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })

        if (response.status === 409) {
            window.location.reload()
            return
        }

        if (!response.ok) {
            this.saving = false
            this.showStatus(this.unsavedValue)
            return
        }

        const payload = await response.json()
        if (payload.conflict) {
            window.location.reload()
            return
        }

        this.applyVersions(payload.versions ?? {})
        this.dirty = false
        this.saving = false
        this.showStatus(this.savedValue)
    }

    applyVersions(versions) {
        Object.entries(versions).forEach(([attributeId, version]) => {
            const field = document.querySelector(`[name="values[${attributeId}][version]"]`)
            if (field instanceof HTMLInputElement) {
                field.value = String(version)
            }
        })
    }

    showStatus(text) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = text
        }
    }
}
