<div class="mt-8 sm:mx-8 lg:px-8">
    <!-- Fortschrittsanzeige -->
    <x-PTF15.progressbar>
        <x-PTF15.progressbar.step completed valid>Step 1</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step completed valid>Step 2</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step active>Step 3</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step>Step 4</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step>Step 5</x-PTF15.progressbar.step>
    </x-PTF15.progressbar>

    <!-- Überschrift -->

    <div class="mx-auto mt-4 sm:mt-6 lg:mt-8">
        <div class="max-w-2xl lg:mx-0">
            <h2 class="text-2xl font-medium tracking-tight text-gray-900 uppercase sm:text-4xl">
                StuRa: Externes Projekt
            </h2>
            <p class="mt-6 text-lg leading-8 text-gray-600">Hinweis Vorsteuerabzugsberechtigung</p>
        </div>
    </div>

    <!-- Formular -->

    <form class="mt-4">
        @csrf
        <div class="space-y-12 sm:space-y-16">
            <div>
                <h2 class="mt-8 text-base font-semibold leading-7 text-gray-900">Ausgaben</h2>

                <x-PTF15.antrag.table>
                    <x-slot name="header">
                        <x-PTF15.antrag.table.header>#</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>Name</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>Beschreibung</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>Betrag</x-PTF15.antrag.table.header>
                    </x-slot>

                    <x-PTF15.antrag.table.row.category id="1" sum="72,33"></x-PTF15.antrag.table.row.category>

                    <x-PTF15.antrag.table.row.posten name="ausgaben" id="1.1"/>

                    <x-PTF15.antrag.table.row.link href="#">Posten hinzufügen</x-PTF15.antrag.table.row.link>
                    <x-PTF15.antrag.table.row.link href="#">Kategorie hinzufügen</x-PTF15.antrag.table.row.link>

                    <x-slot name="footer">
                        <x-PTF15.antrag.table.header>&sum;</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>Gesamtausgaben</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header></x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>72,33 €</x-PTF15.antrag.table.header>
                    </x-slot>

                </x-PTF15.antrag.table>

                <h2 class="mt-8 text-base font-semibold leading-7 text-gray-900">Einnahmen</h2>

                <x-PTF15.antrag.table>
                    <x-slot name="header">
                        <x-PTF15.antrag.table.header>#</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>Name</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>Beschreibung</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>Betrag</x-PTF15.antrag.table.header>
                    </x-slot>

                    <x-PTF15.antrag.table.row.category id="1" sum="72,33">Förderungen</x-PTF15.antrag.table.row.category>

                    <x-PTF15.antrag.table.row>
                        <x-PTF15.antrag.table.cell class="font-medium text-gray-900">1.1</x-PTF15.antrag.table.cell>
                        <x-PTF15.antrag.table.cell><b class="pl-1">StuRa-Förderung</b></x-PTF15.antrag.table.cell>
                        <x-PTF15.antrag.table.cell/>
                        <x-PTF15.antrag.table.cell>
                            <input type="number" min="0.00" step=".01" name="einnahmen-1-betrag" id="einnahmen-1-betrag" class="inline-block w-16 sm:w-24 md:w-32 lg:w-64 rounded-md border-0 py-1.5 text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </x-PTF15.antrag.table.cell>
                    </x-PTF15.antrag.table.row>

                    <x-PTF15.antrag.table.row.posten name="einnahmen" id="1.2"/>
                    <livewire:budgetplan.row name="projectBudgetForm.positions" :values="$projectBudgetForm->positions" topic="1" position="3"/>
                    <livewire:budgetplan.row name="projectBudgetForm.positions" :values="$projectBudgetForm->positions" topic="1" position="4"/>

                    <x-PTF15.antrag.table.row.link href="#">Posten hinzufügen</x-PTF15.antrag.table.row.link>

                    <x-PTF15.antrag.table.row.category id="2" sum="0">Eintritt</x-PTF15.antrag.table.row.category>

                    <x-PTF15.antrag.table.row>
                        <x-PTF15.antrag.table.cell class="font-medium text-gray-900">2.1</x-PTF15.antrag.table.cell>
                        <x-PTF15.antrag.table.cell><b class="pl-1">Studis</b></x-PTF15.antrag.table.cell>
                        <x-PTF15.antrag.table.cell/>
                        <x-PTF15.antrag.table.cell>
                            <input type="number" min="0.00" step=".01" name="einnahmen-1-betrag" id="einnahmen-1-betrag" class="inline-block w-16 sm:w-24 md:w-32 lg:w-64 rounded-md border-0 py-1.5 text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </x-PTF15.antrag.table.cell>
                    </x-PTF15.antrag.table.row>

                    <x-PTF15.antrag.table.row>
                        <x-PTF15.antrag.table.cell class="font-medium text-gray-900">2.2</x-PTF15.antrag.table.cell>
                        <x-PTF15.antrag.table.cell><b class="pl-1">Vollzahler</b></x-PTF15.antrag.table.cell>
                        <x-PTF15.antrag.table.cell/>
                        <x-PTF15.antrag.table.cell>
                            <input type="number" min="0.00" step=".01" name="einnahmen-1-betrag" id="einnahmen-1-betrag" class="inline-block w-16 sm:w-24 md:w-32 lg:w-64 rounded-md border-0 py-1.5 text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </x-PTF15.antrag.table.cell>
                    </x-PTF15.antrag.table.row>

                    <x-PTF15.antrag.table.row.link href="#">Posten hinzufügen</x-PTF15.antrag.table.row.link>
                    <x-PTF15.antrag.table.row.link href="#">Kategorie hinzufügen</x-PTF15.antrag.table.row.link>

                    <x-slot name="footer">
                        <x-PTF15.antrag.table.header>&sum;</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>Gesamteinnahmen</x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header></x-PTF15.antrag.table.header>
                        <x-PTF15.antrag.table.header>72,33 €</x-PTF15.antrag.table.header>
                    </x-slot>

                </x-PTF15.antrag.table>

                <h2 class="mt-8 text-base font-semibold leading-7 text-gray-900">Saldo</h2>

                <x-PTF15.antrag.table stripped>
                    <x-PTF15.antrag.table.row>
                        <x-PTF15.antrag.table.cell>&#8710;</x-PTF15.antrag.table.cell>
                        <x-PTF15.antrag.table.cell><span class="sm:hidden">Einnahmen - Ausgaben =</span></x-PTF15.antrag.table.cell>
                        <x-PTF15.antrag.table.cell>0 €</x-PTF15.antrag.table.cell>
                    </x-PTF15.antrag.table.row>
                </x-PTF15.antrag.table>

                <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">

                    <!-- File Upload -->

                    <!-- Buttons -->

                    <x-PTF15.antrag.row>
                        <div></div>
                        <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <x-PTF15.antrag.button.light wire:click="previousPage()">Zurück</x-PTF15.antrag.button.light>
                            <x-PTF15.antrag.button.primary wire:click="nextPage()">Weiter</x-PTF15.antrag.button.primary>
                        </div>
                    </x-PTF15.antrag.row>

                </div>
            </div>
        </div>

        <div class="flex items-center justify-end mt-6 mb-4 gap-x-6">
            <x-PTF15.antrag.button.light>Antrag abbrechen</x-PTF15.antrag.button.light>
            <x-PTF15.antrag.button.primary>Entwurf speichern</x-PTF15.antrag.button.primary>
        </div>
    </form>
</div>
