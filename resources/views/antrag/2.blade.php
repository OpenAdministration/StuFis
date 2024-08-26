<x-layout>
    <div class="mt-8 sm:mx-8 lg:px-8">
        <!-- Fortschrittsanzeige -->
        <x-progressbar>
            <x-progressbar.step completed valid>Step 1</x-progressbar.step>
            <x-progressbar.step active>Step 2</x-progressbar.step>
            <x-progressbar.step>Step 3</x-progressbar.step>
            <x-progressbar.step>Step 4</x-progressbar.step>
            <x-progressbar.step>Step 5</x-progressbar.step>
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

        <form class="mt-4" wire:submit='saveProjectForm'>
            @csrf
            <div class="space-y-12 sm:space-y-16">
                <div>
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Allgemeine Angaben</h2>

                    <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">
                        <x-antrag.row>
                            <x-antrag.input name="name" wire:model="name">Projektname</x-antrag.input>
                        </x-antrag.row>

                    <!-- Date picker -->

                        <x-antrag.row>
                            <label for="start_date" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Projektstart</label></label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <div class="flex -space-x-px">
                                    <div class="flex-1 w-1/2 min-w-0">
                                        <!--label for="project-start-date" class="sr-only">Startdatum</label-->
                                        <input type="date" name="start_date" id="start_date"  wire:model="start_date" class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
                                    </div>
                                </div>
                            </div>

                            <label for="end_date" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Projektende</label></label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <div class="flex -space-x-px">
                                    <div class="flex-1 w-1/2 min-w-0">
                                        <!--label for="project-end-date" class="sr-only">Enddatum</label-->
                                        <input type="date" name="end_date" id="end_date"  wire:model="end_date" class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
                                    </div>
                                </div>
                            </div>
                        </x-antrag.row>

                    <!-- WYSIWYG Editor -->

                        <x-antrag.row>
                            <x-antrag.textarea name="description" wire:model="description">Projektbeschreibung</x-antrag.input>
                        </x-antrag.row>

                    <!-- TODO: File Upload -->

                    <!-- Checkbox List -->

                        <fieldset>
                            <legend class="sr-only">Aufgaben der Studierendenschaft</legend>
                            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:py-6">
                                <div class="text-sm font-semibold leading-6 text-gray-900" aria-hidden="true">Aufgaben der Studierendenschaft</div>
                                <div class="mt-4 sm:col-span-2 sm:mt-0">
                                    <div class="max-w-lg space-y-6">
                                        <x-antrag.checkbox name="aufgaben-meinungsbildung" label="Meinungsbildung" value="meinungsbildung" wire.model="student_body_duties">
                                            die Meinungsbildung in der Gruppe der Studierenden zu ermöglichen
                                        </x-antrag.checkbox>
                                        <x-antrag.checkbox name="aufgaben-gesellschaft" label="Hochschule und Gesellschaft" value="gesellschaft" wire.model="student_body_duties">
                                            die Belange ihrer Mitglieder in Hochschule und Gesellschaft wahrzunehmen
                                        </x-antrag.checkbox>
                                        <x-antrag.checkbox name="aufgaben-hochschule" label="Erfüllung der Aufgaben der Hochschule" value="hochschule" wire.model="student_body_duties">
                                            an der Erfüllung der Aufgaben der Hochschule (§§ 3 und 4) insbesondere durch Stellungnahmen zu hochschul- oder wissenschaftspolitischen Fragen mitzuwirken
                                        </x-antrag.checkbox>
                                        <x-antrag.checkbox name="aufgaben-politik" label="politische Bildung" value="politik" wire.model="student_body_duties">
                                            auf der Grundlage der verfassungsmäßigen Ordnung die politische Bildung, das staatsbürgerliche Verantwortungsbewusstsein und die Bereitschaft ihrer Mitglieder zur aktiven Toleranz sowie zum Eintreten für die Grund- und Menschenrechte zu fördern
                                        </x-antrag.checkbox>
                                        <x-antrag.checkbox name="aufgaben-kultur" label="Kultur & Soziales" value="kultur" wire.model="student_body_duties">
                                            kulturelle, fachliche, wirtschaftliche und soziale Belange ihrer Mitglieder wahrzunehmen
                                        </x-antrag.checkbox>
                                        <x-antrag.checkbox name="aufgaben-integration" label="Integration ausländischer Studierender" value="integration" wire.model="student_body_duties">
                                            die Integration ausländischer Studierender zu fördern
                                        </x-antrag.checkbox>
                                        <x-antrag.checkbox name="aufgaben-sport" label="Sport" value="sport" wire.model="student_body_duties">
                                            den Studentensport zu fördern
                                        </x-antrag.checkbox>
                                        <x-antrag.checkbox name="aufgaben-internationales" label="Internationales" value="internationales" wire.model="student_body_duties">
                                            die überregionalen und internationalen Studierendenbeziehungen zu pflegen
                                        </x-antrag.checkbox>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                    <!-- Sonstige Eingabefelder -->

                        <x-antrag.row>
                            <x-antrag.input name="target-group"  wire:model="target_group">Zielgruppe</x-antrag.input>
                        </x-antrag.row>

                        <x-antrag.row>
                            <x-antrag.input name="estimated-guests" wire:model="estimated_guests">Erwartete Teilnehmendenzahl</x-antrag.input>
                            <x-antrag.input name="estimated-students" wire:model="estimated_students">Davon Studierende</x-antrag.input>
                        </x-antrag.row>

                    <!-- Buttons -->

                        <x-antrag.row>
                            <div></div>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <x-antrag.link-button.light href="1">Zurück</x-antrag.link-button.light>
                                <x-antrag.link-button.primary href="3">Weiter</x-antrag.link-button.primary>
                            </div>
                        </x-antrag.row>

                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end mt-6 mb-4 gap-x-6">
                <x-antrag.link-button.light>Antrag abbrechen</x-antrag.link-button.light>
                <!--x-antrag.link-button.primary>Entwurf speichern</x-antrag.link-button.primary-->
                <button type="submit" class="inline-flex justify-center px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save</button>
            </div>
        </form>
    </div>
</x-layout>
