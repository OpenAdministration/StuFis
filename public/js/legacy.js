class EmbeddedWebview extends HTMLElement {
    connectedCallback() {
        const shadow = this.attachShadow({ mode: 'closed' });
        //shadow.innerHTML = this.getAttribute('html');
        shadow.innerHTML = this.innerHTML;
        this.innerHTML = "";
    }
}

window.customElements.define(
    'embedded-webview',
    EmbeddedWebview
);
