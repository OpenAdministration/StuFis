class EmbeddedWebview extends HTMLElement {
    connectedCallback() {
        const shadow = this.attachShadow({ mode: 'closed' });
        shadow.innerHTML = this.getAttribute('html');
    }
}

window.customElements.define(
    'embedded-webview',
    EmbeddedWebview
);
