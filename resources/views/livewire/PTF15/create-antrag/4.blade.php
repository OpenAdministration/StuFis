

<div class="mt-8 sm:mx-8 lg:px-8">
    <!-- Fortschrittsanzeige -->
    <x-PTF15.progressbar>
        <x-PTF15.progressbar.step completed valid>Step 1</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step completed valid>Step 2</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step completed valid>Step 3</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step active>Step 4</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step>Step 5</x-PTF15.progressbar.step>
    </x-PTF15.progressbar>

    <!-- Überschrift -->

    <div class="mx-auto mt-4 sm:mt-6 lg:mt-8">
        <div class="max-w-2xl lg:mx-0">
            <h2 class="text-2xl font-medium tracking-tight text-gray-900 uppercase sm:text-4xl">
                StuRa: Externes Projekt
            </h2>
        </div>
    </div>

    <!-- Formular -->

    <form class="mt-4">
        @csrf
        <div class="space-y-12 sm:space-y-16">
            <div>


                <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">

                    <x-PTF15.antrag.row>
                        <x-PTF15.antrag.input.number name="stura-funding" readonly value="72.33">StuRa-Förderung €</x-PTF15.antrag.input.number>
                    </x-PTF15.antrag.row>

                    <x-PTF15.antrag.row>
                        Vorkasse beantragen:
                    </x-PTF15.antrag.row>

                    <x-PTF15.antrag.row>
                        <x-PTF15.antrag.input.number name="advance-payment" max="72.33" value="0">
                            Höhe der Vorkasse in €
                        </x-PTF15.antrag.input.number>
                    </x-PTF15.antrag.row>


                    <x-PTF15.antrag.row>
                        <x-PTF15.antrag.form-group name="attachments" label="Weitere Anhänge">

                            <!-- Additional File Uploads -->

                        </x-PTF15.antrag.form-group>
                    </x-PTF15.antrag.row>

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
