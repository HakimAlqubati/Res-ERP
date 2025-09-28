<div x-data="qt({ quotes: @js($quotes ?? []), i: {{ $startIndex ?? 0 }}, every: {{ $rotateEvery ?? 20 }} })"
    class="ms-2 hidden md:flex items-center text-xs text-gray-500 max-w-[260px] space-x-1" title="">
    <div class="overflow-hidden max-w-full">
        <span x-ref="t" x-text="q()" class="inline-block whitespace-nowrap transition-opacity duration-20"
            style="
                padding: 3px 14px;
                border: 2px solid #0d7c66;
                border-top-right-radius: 10px;
                border-bottom-left-radius: 10px;
                font-weight: 900;
                font-size: 0.9rem;
                letter-spacing: 0.5px;
                color: #0d7c66;
                box-shadow: 0 0 4px rgba(13,124,102,0.6),
                            0 0 8px rgba(13,124,102,0.4);
                transition: box-shadow 0.3s ease-in-out;
                /* background: #fff; */
            "></span>
    </div>

    <style>
        /* حركة خفيفة عند طول النص */
        .qt-marquee {
            padding-left: 100%;
            animation: qt-scroll 10s linear infinite;
        }

        @keyframes qt-scroll {
            to {
                transform: translateX(-100%);
            }
        }
    </style>
</div>

<script>
    function qt({
        quotes,
        i = 0,
        every = 20
    }) {
        return {
            quotes,
            i,
            every: 20,
            q() {
                return this.quotes?.[this.i] ?? '';
            },
            init() {
                this.$nextTick(() => this.m());
                setInterval(() => {
                    if ((this.quotes?.length || 0) > 1) {
                        this.i = (this.i + 1) % this.quotes.length;
                        this.f();
                    }
                }, this.every * 1000);
                window.addEventListener('resize', () => this.m());
            },
            f() {
                this.$refs.t.classList.add('opacity-0');
                setTimeout(() => {
                    this.$refs.t.textContent = this.q();
                    this.$refs.t.classList.remove('opacity-0');
                    this.m();
                }, 180);
            },
            m() {
                const el = this.$refs.t,
                    c = el.parentElement;
                el.classList.remove('qt-marquee');
                if (el.scrollWidth > c.clientWidth + 6) el.classList.add('qt-marquee');
            },
        }
    }
</script>
