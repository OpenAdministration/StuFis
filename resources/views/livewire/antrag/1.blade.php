<x-layout>
    <div class="mt-8 sm:mx-8 lg:px-8">
        <!-- Fortschrittsanzeige -->
        <x-progressbar>
            <x-progressbar.step active>Step 1</x-progressbar.step>
            <x-progressbar.step >Step 2</x-progressbar.step>
            <x-progressbar.step >Step 3</x-progressbar.step>
            <x-progressbar.step >Step 4</x-progressbar.step>
            <x-progressbar.step >Step 5</x-progressbar.step>
        </x-progressbar>

        <!-- Überschrift -->

        <div class="mx-auto mt-4 sm:mt-6 lg:mt-8">
            <div class="max-w-2xl lg:mx-0">
                <h2 class="text-2xl font-medium tracking-tight text-gray-900 uppercase sm:text-4xl">
                    StuRa: Externes Projekt
                </h2>
            </div>
        </div>

        <!-- Formular -->

        <form class="mt-4" >
            @csrf
            <div class="space-y-12 sm:space-y-16">
                <div>
                    <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">

                        <!-- dropdown -->
                        <x-antrag.row>
                            <x-antrag.select name="applicant-type">Antragsteller</x-antrag.select>
                        </x-antrag.row>

                        <!-- dropdown -->
                        <x-antrag.row>
                            <x-antrag.select name="salutation">Anrede</x-antrag.select>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="org-name">Name der Organisation</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="name">Name Ansprechpartner:in</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="street-and-number">Straße und Hausnummer</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="zip-and-place">PLZ und Ort</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="email">E-Mail Adresse</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="phone">Telefonnummer</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="iban">IBAN</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="bic">BIC</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.form-group name="nonprofit" label="Gemeinnützugkeit">

                                <!-- Radio Boxen -->

                            </x-antrag.form-group>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.form-group name="tax-deduction" label="Vorsteuerabzugsberechtigt">

                                <!-- Radio Boxen -->

                            </x-antrag.form-group>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="register-number">Registernummer</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="website">Webseite</x-antrag.input>
                        </x-antrag.row>

                        <!-- dropdown -->
                        <x-antrag.row>
                            <x-antrag.select name="statur-group">Statusgruppe</x-antrag.select>
                        </x-antrag.row>


                    <!-- Buttons -->

                        <x-antrag.row>
                            <div></div>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <x-antrag.link-button.light href="/">Zurück</x-antrag.link-button.light>
                                <x-antrag.link-button.primary href="/antrag/2">Weiter</x-antrag.link-button.primary>
                            </div>
                        </x-antrag.row>

                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end mt-6 mb-4 gap-x-6">
                <x-antrag.link-button.light>Antrag abbrechen</x-antrag.link-button.light>
                <x-antrag.link-button.primary>Entwurf speichern</x-antrag.link-button.primary>
            </div>
        </form>
    </div>
</x-layout>
