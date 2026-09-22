import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        url: String,
        interval: { type: Number, default: 3000 },
    }

    connect() {
        this.timer = window.setInterval(() => {
            void this.refresh()
        }, this.intervalValue)
    }

    disconnect() {
        window.clearInterval(this.timer)
    }

    async refresh() {
        if (document.hidden) {
            return
        }

        const response = await fetch(this.urlValue, {
            headers: { Accept: 'text/html' },
            credentials: 'same-origin',
        })
        if (!response.ok) {
            return
        }

        this.element.innerHTML = await response.text()
    }
}
