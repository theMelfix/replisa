<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Contatti</h1>

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cerca per nome o telefono…"
                   class="flex-1 sm:max-w-96 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            @if ($allTags->isNotEmpty())
                <select wire:model.live="tagFilter" class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                    <option value="">Tutte le etichette</option>
                    @foreach ($allTags as $tag)
                        <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500 border-b dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Telefono</th>
                        <th class="px-4 py-3">Etichette</th>
                        <th class="px-4 py-3">Opt-in</th>
                        <th class="px-4 py-3">Ultimo contatto</th>
                        <th class="px-4 py-3 text-right">Messaggi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $c)
                        <tr class="border-b dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            wire:click="showConversation({{ $c->id }})" wire:key="contact-{{ $c->id }}">
                            <td class="px-4 py-3 font-medium">{{ $c->name ?: '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $c->phone }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($c->tags as $tag)
                                        <span class="text-[10px] font-medium rounded-full px-2 py-0.5 bg-indigo-100 text-indigo-700">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($c->opted_in)
                                    <span class="text-xs font-medium rounded-full px-2 py-0.5 bg-green-100 text-green-800">Sì</span>
                                @else
                                    <span class="text-xs font-medium rounded-full px-2 py-0.5 bg-gray-100 text-gray-600">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $c->last_seen_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $c->messages_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Nessun contatto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $contacts->links() }}</div>
    </div>

    {{-- Pannello dettaglio conversazione (E4.2.2) --}}
    @if ($selectedContact)
        <div class="fixed inset-0 z-40 overflow-hidden" wire:key="conversation-{{ $selectedContact->id }}">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-gray-900/40" wire:click="closeConversation"></div>

            {{-- Slide-over --}}
            <div class="absolute inset-y-0 right-0 w-full max-w-md bg-gray-50 dark:bg-gray-900 shadow-xl flex flex-col">
                <header class="flex items-center justify-between px-5 py-4 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ $selectedContact->name ?: 'Contatto' }}</h2>
                        <p class="text-xs font-mono text-gray-500">{{ $selectedContact->phone }}</p>
                    </div>
                    <button type="button" wire:click="closeConversation" aria-label="Chiudi"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
                </header>

                {{-- Etichette del contatto (E3.4.3): base per segmentare le campagne --}}
                <div class="px-5 py-3 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs text-gray-500">Etichette:</span>
                        @forelse ($selectedContact->tags as $tag)
                            <span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2 py-0.5 bg-indigo-100 text-indigo-700" wire:key="tag-{{ $tag->id }}">
                                {{ $tag->name }}
                                <button type="button" wire:click="removeTag({{ $tag->id }})" class="text-indigo-400 hover:text-indigo-700" aria-label="Rimuovi">&times;</button>
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">nessuna</span>
                        @endforelse
                    </div>
                    <div class="mt-2 flex gap-2">
                        <input type="text" wire:model="newTag" wire:keydown.enter="addTag" maxlength="50"
                               list="tag-suggestions" placeholder="Aggiungi etichetta…"
                               class="flex-1 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-xs py-1">
                        <datalist id="tag-suggestions">
                            @foreach ($allTags as $tag)
                                <option value="{{ $tag->name }}"></option>
                            @endforeach
                        </datalist>
                        <button type="button" wire:click="addTag"
                                class="px-3 py-1 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-md">Aggiungi</button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-5 space-y-3">
                    @forelse ($conversation as $m)
                        @php($out = $m->direction === \App\Models\Message::DIRECTION_OUTBOUND)
                        <div class="flex {{ $out ? 'justify-end' : 'justify-start' }}" wire:key="msg-{{ $m->id }}">
                            <div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm {{ $out ? 'bg-green-600 text-white rounded-br-sm' : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 border border-gray-100 dark:border-gray-700 rounded-bl-sm' }}">
                                <p class="whitespace-pre-wrap break-words">{{ $m->displayText() }}</p>
                                <div class="mt-1 flex items-center gap-1 text-[10px] {{ $out ? 'text-green-100' : 'text-gray-400' }}">
                                    <span>{{ $m->created_at->format('d/m H:i') }}</span>
                                    @if ($out)
                                        <span>· {{ $m->status }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-gray-500 py-10">Nessun messaggio con questo contatto.</p>
                    @endforelse
                </div>

                <footer class="px-5 py-3 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500">
                    Cronologia in sola lettura — ultimi {{ \App\Livewire\Contacts::CONVERSATION_LIMIT }} messaggi.
                </footer>
            </div>
        </div>
    @endif
</div>
