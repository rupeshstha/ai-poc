<div
    x-data="{
        toasts: [],
        add(event) {
            const id = Date.now();
            const toast = {
                id,
                text: event.detail.slots?.text ?? '',
                heading: event.detail.slots?.heading ?? null,
                variant: event.detail.dataset?.variant ?? 'default',
                duration: event.detail.duration ?? 5000,
            };
            this.toasts.push(toast);
            if (toast.duration > 0) {
                setTimeout(() => this.remove(id), toast.duration);
            }
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @toast-show.window="add($event)"
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-80"
    role="region"
    aria-label="Notifications"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            :class="{
                'border-red-500 dark:border-red-500': toast.variant === 'danger',
                'border-green-500 dark:border-green-500': toast.variant === 'success',
                'border-yellow-500 dark:border-yellow-500': toast.variant === 'warning',
                'border-zinc-200 dark:border-zinc-700': !['danger','success','warning'].includes(toast.variant),
            }"
            class="relative flex flex-col gap-1 rounded-lg border bg-white dark:bg-zinc-900 px-4 py-3 shadow-lg pr-8"
        >
            <template x-if="toast.heading">
                <p
                    :class="{
                        'text-red-600 dark:text-red-400': toast.variant === 'danger',
                        'text-green-600 dark:text-green-400': toast.variant === 'success',
                        'text-yellow-600 dark:text-yellow-400': toast.variant === 'warning',
                        'text-zinc-900 dark:text-zinc-100': !['danger','success','warning'].includes(toast.variant),
                    }"
                    class="text-sm font-semibold"
                    x-text="toast.heading"
                ></p>
            </template>
            <template x-if="toast.text">
                <p class="text-sm text-zinc-600 dark:text-zinc-400" x-text="toast.text"></p>
            </template>
            <button
                @click="remove(toast.id)"
                class="absolute top-2 right-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                aria-label="Dismiss"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
