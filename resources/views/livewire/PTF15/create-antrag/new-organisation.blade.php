<div class="mt-8 sm:mx-8 lg:px-8">
    <div class="space-y-12 sm:space-y-16">
        <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">
            <x-PTF15.antrag.row>
                <x-PTF15.antrag.input wire:model="orgForm.name">Name der Organisation</x-PTF15.antrag.input>
            </x-PTF15.antrag.row>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.input wire:model="orgForm.name">Name Ansprechpartner:in</x-PTF15.antrag.input>
            </x-PTF15.antrag.row>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.input wire:model="orgForm.street">Straße und Hausnummer</x-PTF15.antrag.input>
            </x-PTF15.antrag.row>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.input wire:model="orgForm.zip_code">PLZ</x-PTF15.antrag.input>
            </x-PTF15.antrag.row>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.input wire:model="orgForm.city">Ort</x-PTF15.antrag.input>
            </x-PTF15.antrag.row>

            <livewire:array-input name="orgForm.mails" :values="$orgForm->mails" label="E-Mail Adressen"/>
            <livewire:array-input name="orgForm.phones" :values="$orgForm->phones" label="Telefonnummern"/>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.input wire:model="orgForm.iban">IBAN</x-PTF15.antrag.input>
            </x-PTF15.antrag.row>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.input wire:model="orgForm.bic">BIC</x-PTF15.antrag.input>
            </x-PTF15.antrag.row>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.form-group name="orgForm.charitable" label="Gemeinnützigkeit">
                    <x-PTF15.antrag.radiobox wire:model="orgForm.charitable" id="charitable-yes" value="yes">Wir sind als gemeinnützig registriert</x-PTF15.antrag.radiobox>
                    <x-PTF15.antrag.radiobox wire:model="orgForm.charitable" id="charitable-no" value="no">Wir sind nicht als gemeinnützig registriert</x-PTF15.antrag.radiobox>
                </x-PTF15.antrag.form-group>
            </x-PTF15.antrag.row>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.form-group name="orgForm.tax-deduction-entitlement" label="Vorsteuerabzugsberechtigung">
                    <x-PTF15.antrag.radiobox wire:model="orgForm.tax-deduction-entitlement" id="tax-deduction-entitlement-yes">Vorhanden</x-PTF15.antrag.radiobox>
                    <x-PTF15.antrag.radiobox wire:model="orgForm.tax-deduction-entitlement" id="tax-deduction-entitlement-no">Nicht vorhanden</x-PTF15.antrag.radiobox>
                </x-PTF15.antrag.form-group>
            </x-PTF15.antrag.row>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.input wire:model="orgForm.register-number">Registernummer</x-PTF15.antrag.input>
            </x-PTF15.antrag.row>

            <x-PTF15.antrag.row>
                <x-PTF15.antrag.input wire:model="orgForm.website">Webseite</x-PTF15.antrag.input>
            </x-PTF15.antrag.row>


            <x-PTF15.antrag.button.primary wire:click="create()">Speichern</x-PTF15.antrag.button.primary>
        </div>
    </div>
</div>
