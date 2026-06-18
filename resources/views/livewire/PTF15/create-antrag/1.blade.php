<div class="mt-8 sm:mx-8 lg:px-8">
    <!-- Fortschrittsanzeige -->
    <x-PTF15.progressbar steps="5">
        <x-PTF15.progressbar.step active>Step 1</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step>Step 2</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step>Step 3</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step>Step 4</x-PTF15.progressbar.step>
        <x-PTF15.progressbar.step>Step 5</x-PTF15.progressbar.step>
    </x-PTF15.progressbar>

    <!-- Überschrift -->

    <div class="mx-auto mt-4 sm:mt-6 lg:mt-8">
        <div class="max-w-2xl lg:mx-0">
            <h2 class="text-2xl font-medium tracking-tight text-gray-900 uppercase sm:text-4xl">
                StuRa: Externes Projekt - Antragssteller*innen
            </h2>
        </div>
    </div>

    <!-- Formular -->
    <div class="space-y-12 sm:space-y-16">
        <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.select wire:model="organisation_id" label="Ich stelle den Antrag für">
                    <option value="">Ohne Träger-Organisation</option>
                    @foreach($orgs as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </x-PTF15.antrag.select>
                <x-PTF15.antrag.link-button.light :href="route('antrag.new-org')"><x-fas-plus/> Neue Organisation</x-PTF15.antrag.link-button.light>
            </x-PTF15.antrag.row>
            <x-PTF15.antrag.row>
                <x-PTF15.antrag.select wire:model="user_id" label="Ansprechpartner">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-PTF15.antrag.select>

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

    <div class="flex items-center justify-end mt-6 mb-4 gap-x-6">
        <x-PTF15.antrag.link-button.light href="{{ route('home') }}" >Antrag abbrechen</x-PTF15.antrag.link-button.light>
        <x-PTF15.antrag.button.primary>Entwurf speichern</x-PTF15.antrag.button.primary>
    </div>
</div>
