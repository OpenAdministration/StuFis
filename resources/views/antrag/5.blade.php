<x-layout>
    <div class="mt-8 sm:mx-8 lg:px-8">
        <!-- Fortschrittsanzeige -->
        <x-progressbar>
            <x-progressbar.step completed valid>Step 1</x-progressbar.step>
            <x-progressbar.step completed valid>Step 2</x-progressbar.step>
            <x-progressbar.step completed valid>Step 3</x-progressbar.step>
            <x-progressbar.step completed valid>Step 4</x-progressbar.step>
            <x-progressbar.step active>Step 5</x-progressbar.step>
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

        <!-- Antragssteller:in Org bzw Name -->
            <!-- Assoziationen (zb Fakultät etc.) -->
            <!-- Kontaktdaten -->
            <!-- Bankverbindung -->
        <!-- Projektname + Projektzeitraum -->
            <!-- Projektbeschreibung -->
            <!-- Zielgruppe, TN-Zahl... -->
            <!-- Aufgabenbereiche HSG -->
        <!-- Finanzplanung: Volumen, Förderung, Vorkasse -->
        <!-- Anhänge -->

        <!--
            Herr Lukas Staab
            Open Administration UG

            Am Stollen 11
            98693 Ilmenau

            Student:in, Informatik, TU Ilmenau

            lukas.staab@open-administration.de
            0177 123456789

            DE12 3456 7890 1234 5678 90
            BLABLIBLUB24X

            www.open-administration.de
            R-123/456/789

            nicht gemeinnützig, vorsteuerabzugsberechtigt
        -->

        <!--
            Sommerfest 5.7.-7.7.2024

            Anlässlich meines persönlichen Jubiläums organisiere ich ein Open Administration Sommerfest.
            Eingeladen sind alle angehörigen der TU Ilmenau. Es wird gegrillt, für Grillgut (sowohl Fleisch als auch Veggie)
            und Getränke sowie für Deko und Fahrtkosten beim Einkauf benötige ich eine Förderung.

            Aufgaben nach LHG: Kultur & Soziales

            Zielgruppe: Meine Freund:innen, Angehörige der TU Ilmenau
            200 Teilnehmende (80% Studierende)
        -->

        <!--
            Finanzplan Volumen: 524€
            Antragssumme: 424€
            Vorkasse: 0€
        -->


        <form class="mt-4">
            @csrf
            <div class="space-y-12 sm:space-y-16">
                <div>


                    <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">
                        <x-summary-card>
                            <x-slot name="heading">
                                <x-heading title="Herr Lukas Staab, Open Administration UG" subtitle="Student:in, Informatik, TU Ilmenau" />
                            </x-slot>
                            <x-dl.element label="Antragssteller:in">
                                Herr Lukas Staab,
                                Open Administration UG
                            </x-dl.element>
                            <x-dl.element label="Adresse">
                                Am Stollen 11<br/>
                                98693 Ilmenau
                            </x-dl.element>
                            <x-dl.element label="Statusgruppe">
                                Student:in, Informatik, TU Ilmenau
                            </x-dl.element>
                            <x-dl.element label="Kontaktdaten">
                                <x-fas-envelope class="inline w-5 h-5 p-1 pt-0"/><a href="mailto:lukas.staab@open-administration.de">lukas.staab@open-administration.de</a><br/>
                                <x-fas-phone class="inline w-5 h-5 p-1 pt-0"/><a href="tel:0177123456789">0177 123456789</a>
                            </x-dl.element>
                            <x-dl.element label="Bankverbindung">
                                DE12 3456 7890 1234 5678 90<br/>
                                BLABLIBLUB24X
                            </x-dl.element>
                            <x-dl.element label="Sonstige Angaben">
                                www.open-administration.de<br/>
                                R-123/456/789<br/>
                                nicht gemeinnützig, vorsteuerabzugsberechtigt
                            </x-dl.element>
                            <x-attachments>
                                <x-attachment-item file="resume_back_end_developer.pdf" size="2.4mb" />
                                <x-attachment-item file="coverletter_back_end_developer.pdf" size="4.5mb" />
                            </x-attachments>
                          </x-summary-card>


                        <x-antrag.row>
                            <div></div>
                            <a href="https://tailwindui.com/components/application-ui/data-display/description-lists" class="hover:underline hover:text-indigo-600">UI Components for Summary</a>
                        </x-antrag.row>

                        <fieldset>
                            <legend class="sr-only">Terms &amp; Conditions</legend>
                            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:py-6">
                                <div class="text-sm font-semibold leading-6 text-gray-900" aria-hidden="true"></div>
                                <div class="mt-4 sm:col-span-2 sm:mt-0">
                                    <div class="max-w-lg space-y-6">
                                        <x-antrag.checkbox name="agb-ckeck" label="Ich habe alles gelesen"></x-antrag.checkbox>
                                        <x-antrag.checkbox name="privacy-check" label="Ich akzeptiere Dingsbums"></x-antrag.checkbox>
                                        <x-antrag.checkbox name="notifications-check" label="Ich möchte E-Mails erhalten"></x-antrag.checkbox>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                    <!-- Buttons -->

                        <x-antrag.row>
                            <div></div>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <x-antrag.link-button.light href="4">Zurück</x-antrag.link-button.light>
                                <x-antrag.link-button.primary>Antrag einreichen</x-antrag.link-button.primary>
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
