<div class="mt-8 sm:mx-8 lg:px-8">
    <div class="space-y-12 sm:space-y-16">
        <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">
            <x-antrag.row>
                <x-antrag.select wire:model="applicant-type">Antragsteller</x-antrag.select>
            </x-antrag.row>

            <x-antrag.row>
                <x-antrag.input wire:model="organisationForm.name">Name der Organisation</x-antrag.input>
            </x-antrag.row>

            <x-antrag.row>
                <x-antrag.input wire:model="userForm.name">Name Ansprechpartner:in</x-antrag.input>
            </x-antrag.row>

            <x-antrag.row>
                <x-antrag.input wire:model="adressForm.street-and-number">Straße und Hausnummer</x-antrag.input>
            </x-antrag.row>

            <x-antrag.row>
                <x-antrag.input wire:model="adressForm.zip-and-place">PLZ und Ort</x-antrag.input>
            </x-antrag.row>

            <livewire:array-input name="userForm.mails" :values="$userForm->mails" label="E-Mail Adressen"/>
            <livewire:array-input name="userForm.phones" :values="$userForm->phones" label="Telefonnummern"/>

            <x-antrag.row>
                <x-antrag.input wire:model="userForm.iban">IBAN</x-antrag.input>
            </x-antrag.row>

            <x-antrag.row>
                <x-antrag.input wire:model="userForm.bic">BIC</x-antrag.input>
            </x-antrag.row>

            <x-antrag.row>
                <x-antrag.form-group name="organisationForm.charitable" label="Gemeinnützigkeit">
                    <x-antrag.radiobox wire:model="organisationForm.charitable" id="charitable-yes" value="yes">Wir sind als gemeinnützig registriert</x-antrag.radiobox>
                    <x-antrag.radiobox wire:model="organisationForm.charitable" id="charitable-no" value="no">Wir sind nicht als gemeinnützig registriert</x-antrag.radiobox>
                </x-antrag.form-group>
            </x-antrag.row>

            <x-antrag.row>
                <x-antrag.form-group name="organisationForm.tax-deduction-entitlement" label="Vorsteuerabzugsberechtigung">
                    <x-antrag.radiobox wire:model="organisationForm.tax-deduction-entitlement" id="tax-deduction-entitlement-yes">Vorhanden</x-antrag.radiobox>
                    <x-antrag.radiobox wire:model="organisationForm.tax-deduction-entitlement" id="tax-deduction-entitlement-no">Nicht vorhanden</x-antrag.radiobox>
                </x-antrag.form-group>
            </x-antrag.row>

            <x-antrag.row>
                <x-antrag.input wire:model="organisationForm.register-number">Registernummer</x-antrag.input>
            </x-antrag.row>

            <x-antrag.row>
                <x-antrag.input wire:model="organisationForm.website">Webseite</x-antrag.input>
            </x-antrag.row>



        </div>
    </div>
</div>
