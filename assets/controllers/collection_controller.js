import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['list']

    add(event) {
        event.preventDefault()
        const prototype = this.element.querySelector('template')
        if (!(prototype instanceof HTMLTemplateElement)) {
            return
        }

        this.listTarget.insertAdjacentHTML(
            'beforeend',
            prototype.innerHTML.replaceAll('__index__', String(Date.now())),
        )
    }

    remove(event) {
        event.preventDefault()
        event.currentTarget.closest('[data-collection-target="row"]')?.remove()
    }
}
