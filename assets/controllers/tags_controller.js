import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'chips', 'entry']

    connect() {
        this.render()
    }

    add(event) {
        if (event.key !== 'Enter' && event.key !== ',') {
            return
        }

        event.preventDefault()
        this.push(this.entryTarget.value)
        this.entryTarget.value = ''
    }

    blur() {
        this.push(this.entryTarget.value)
        this.entryTarget.value = ''
    }

    remove(event) {
        const tag = event.currentTarget.dataset.tag
        this.inputTarget.value = this.tags().filter((item) => item !== tag).join(', ')
        this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }))
        this.render()
    }

    push(raw) {
        const tag = raw.trim().replace(/,$/, '').trim()
        if (tag === '') {
            return
        }

        const tags = this.tags()
        if (!tags.includes(tag)) {
            tags.push(tag)
            this.inputTarget.value = tags.join(', ')
            this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }))
            this.render()
        }
    }

    tags() {
        return this.inputTarget.value
            .split(',')
            .map((item) => item.trim())
            .filter((item) => item !== '')
    }

    render() {
        this.chipsTarget.innerHTML = ''
        this.tags().forEach((tag) => {
            const chip = document.createElement('button')
            chip.type = 'button'
            chip.className = 'badge rounded-pill text-bg-secondary border-0'
            chip.dataset.tag = tag
            chip.setAttribute('data-action', 'click->tags#remove')
            chip.textContent = `${tag} ×`
            this.chipsTarget.appendChild(chip)
        })
    }
}
