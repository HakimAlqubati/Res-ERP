{{-- Search input that filters sidebar navigation items in place --}}
<div class="p-3 border-b border-gray-200 dark:border-gray-800" x-data="wbSidebarFilter()">
    <div class="relative" dir="auto" style="text-align: center;">
        <input style="padding: 0px 20px 0px 20px;width: 80%;border: 1px solid;border-radius: 5px;" x-model="q"
            x-on:input.debounce.150ms="filter()" type="text" placeholder="Search in menu..."
            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 text-sm px-3 py-2"
            aria-label="Sidebar search" />
    </div>

    {{-- No-results hint --}}
    <p x-show="noResults" class="mt-2 text-xs text-gray-500" x-cloak>No matching items.</p>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('wbSidebarFilter', () => ({
            q: '',
            noResults: false,

            // Collect sidebar context: items and groups/sections
            getCtx() {
                // Prefer the nearest <aside> that contains this script, fallback to the first aside
                const aside = document.currentScript?.parentElement?.closest('aside') ??
                    document.querySelector('aside');
                if (!aside) return {};

                // Try multiple selectors to support different Filament versions/themes
                const itemSelectors = [
                    'nav a.fi-sidebar-item',
                    'nav a.fi-sidebar-nav-item',
                    'nav li a[href]:not([href="#"])',
                ];
                let items = [];
                for (const sel of itemSelectors) {
                    const found = aside.querySelectorAll(sel);
                    if (found.length) {
                        items = found;
                        break;
                    }
                }

                // Sidebar groups/sections
                const groupSelectors = [
                    '.fi-sidebar-group',
                    'nav section',
                    'nav .fi-sidebar-group-container',
                ];
                let groups = [];
                for (const sel of groupSelectors) {
                    const found = aside.querySelectorAll(sel);
                    if (found.length) {
                        groups = found;
                        break;
                    }
                }

                return {
                    aside,
                    items,
                    groups
                };
            },

            // Normalize text for more reliable matching
            normalize(str) {
                return (str ?? '')
                    .toString()
                    .toLowerCase()
                    // remove Arabic diacritics and collapse spaces for robust matching
                    .replace(/[\u0610-\u061A\u064B-\u065F\u06D6-\u06ED]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();
            },

            // Multi-word "AND" matching
            matches(text, query) {
                const t = this.normalize(text);
                const q = this.normalize(query);
                if (!q) return true;
                const words = q.split(' ').filter(Boolean);
                return words.every(w => t.includes(w));
            },

            // Apply filtering to items and hide empty groups
            filter() {
                const {
                    items,
                    groups
                } = this.getCtx();
                if (!items || items.length === 0) return;

                let visibleCount = 0;

                // Show/hide individual items
                items.forEach((a) => {
                    const label = a.getAttribute('title') || a.innerText || a.textContent ||
                        '';
                    const isMatch = this.matches(label, this.q);

                    const li = a.closest('li') || a;
                    li.style.display = isMatch ? '' : 'none';
                    if (isMatch) visibleCount++;
                });

                // Show/hide groups that have no visible items
                if (groups && groups.length) {
                    groups.forEach((group) => {
                        const visibleLinks = group.querySelectorAll(
                            'a[href]:not([href="#"])');
                        let hasVisible = false;
                        visibleLinks.forEach(link => {
                            const li = link.closest('li') || link;
                            if (li.style.display !== 'none') hasVisible = true;
                        });
                        group.style.display = hasVisible ? '' : 'none';
                    });
                }

                this.noResults = (visibleCount === 0 && this.q.trim().length > 0);
            },

            // Optional: initial run (useful when navigating back)
            init() {
                this.$nextTick(() => this.filter());
            },
        }))
    })
</script>
