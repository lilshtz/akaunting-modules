<x-layouts.admin>
    <x-slot name="title">{{ trans('general.title.new', ['type' => trans('double-entry::general.journal')]) }}</x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="journal-form" method="POST" route="double-entry.journals.store">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('double-entry::general.journal') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <div class="mb-4">
                            <label class="text-sm">{{ trans('double-entry::general.journal_number') }}</label>
                            <div class="mt-1 px-3 py-2 border rounded-lg bg-gray-50">{{ $journalNumber }}</div>
                        </div>
                        <x-form.group.date name="date" label="{{ trans('double-entry::general.journal_date') }}" value="{{ now()->format('Y-m-d') }}" />
                        <x-form.group.text name="reference" label="{{ trans('double-entry::general.reference') }}" not-required />
                        <x-form.group.textarea name="description" label="{{ trans('general.description') }}" not-required />
                        <x-form.group.toggle name="is_recurring" label="Recurring" :value="false" />
                        <x-form.group.select name="recurring_frequency" label="Recurring Frequency" :options="['weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly']" not-required />
                        <x-form.group.date name="next_run_at" label="Next Run" not-required />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('double-entry::general.lines') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <div id="journal-lines" class="space-y-4"></div>

                        <x-button type="button" onclick="addJournalLine()">
                            Add Line
                        </x-button>
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="double-entry.journals.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>

        <template id="journal-line-template">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 journal-line-row">
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.account') }}</label>
                    <select class="w-full border rounded-lg px-3 py-2" data-name="account_id">
                        <option value="">--</option>
                        @foreach ($accounts as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.debit') }}/{{ trans('double-entry::general.credit') }}</label>
                    <select class="w-full border rounded-lg px-3 py-2" data-name="entry_type">
                        <option value="debit">{{ trans('double-entry::general.debit') }}</option>
                        <option value="credit">{{ trans('double-entry::general.credit') }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm">{{ trans('general.amount') }}</label>
                    <input type="number" step="0.0001" class="w-full border rounded-lg px-3 py-2" data-name="amount">
                </div>
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.line_description') }}</label>
                    <div class="flex gap-2">
                        <input type="text" class="w-full border rounded-lg px-3 py-2" data-name="description">
                        <button type="button" class="px-3 py-2 border rounded-lg" onclick="this.closest('.journal-line-row').remove()">X</button>
                    </div>
                </div>
            </div>
        </template>

        <script>
            let journalLineIndex = 0;

            function addJournalLine(values = {}) {
                const template = document.getElementById('journal-line-template').content.cloneNode(true);
                const row = template.querySelector('.journal-line-row');

                row.querySelectorAll('[data-name]').forEach((element) => {
                    const name = element.getAttribute('data-name');
                    element.name = `lines[${journalLineIndex}][${name}]`;

                    if (values[name] !== undefined) {
                        element.value = values[name];
                    }
                });

                document.getElementById('journal-lines').appendChild(template);
                journalLineIndex++;
            }

            addJournalLine();
            addJournalLine({ entry_type: 'credit' });
        </script>
    </x-slot>
</x-layouts.admin>
