/*
 * <pcu-class-select> wraps a select of the CSS class selection widget and turns it
 * into a searchable Choices.js field (Choices is loaded globally by the Contao back
 * end). Unlike Contao's own choices controller, the search is always enabled and
 * matches the option label as well as the CSS class (the option value).
 *
 * A custom element connects and disconnects by itself, so it works with Turbo
 * navigation without access to Contao's Stimulus application.
 */
(() => {
    if (window.customElements.get('pcu-class-select')) {
        return;
    }

    class PcuClassSelect extends HTMLElement {
        connectedCallback() {
            // While the page is parsed, the element connects before its <select> child exists
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init(), { once: true });
            } else {
                this.init();
            }
        }

        init() {
            const select = this.querySelector('select');

            if (!this.isConnected) {
                return;
            }

            if (!select || this.choices || typeof window.Choices === 'undefined') {
                return;
            }

            const lang = window.Contao?.lang ?? {};

            this.choices = new window.Choices(select, {
                shouldSort: false,
                allowHTML: false,
                removeItemButton: select.multiple,
                searchEnabled: true,
                searchFields: ['label', 'value'],
                searchResultLimit: -1,
                searchPlaceholderValue: this.dataset.searchPlaceholder || null,
                itemSelectText: '',
                classNames: {
                    containerOuter: ['choices', ...Array.from(select.classList)],
                },
                fuseOptions: {
                    includeScore: true,
                    threshold: 0.4,
                },
                ...(lang.noResults ? { noResultsText: lang.noResults } : {}),
                ...(lang.noOptions ? { noChoicesText: lang.noOptions } : {}),
                ...(lang.loading ? { loadingText: lang.loading } : {}),
            });
        }

        disconnectedCallback() {
            this.destroy();
        }

        destroy() {
            this.choices?.destroy();
            this.choices = null;
        }
    }

    window.customElements.define('pcu-class-select', PcuClassSelect);

    // Let Choices restore the original select before Turbo caches the page
    document.addEventListener('turbo:before-cache', () => {
        document.querySelectorAll('pcu-class-select').forEach((element) => element.destroy());
    });
})();
