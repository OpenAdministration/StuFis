<x-layout>
    <div class="mt-8 sm:mx-8 lg:px-8">
        <!-- Fortschrittsanzeige -->
        <x-progressbar>
            <x-progressbar.step completed valid>Step 1</x-progressbar.step>
            <x-progressbar.step completed>Step 2</x-progressbar.step>
            <x-progressbar.step active>Step 3</x-progressbar.step>
            <x-progressbar.step>Step 4</x-progressbar.step>
            <x-progressbar.step>Step 5</x-progressbar.step>
        </x-progressbar>

        <!-- Überschrift -->

        <div class="mx-auto mt-4 sm:mt-6 lg:mt-8">
            <div class="max-w-2xl lg:mx-0">
            <h2 class="text-2xl font-medium tracking-tight text-gray-900 uppercase sm:text-4xl">
                StuRa: Externes Projekt
            </h2>
            <p class="mt-6 text-lg leading-8 text-gray-600">Anim aute id magna aliqua ad ad non deserunt sunt. Qui irure qui lorem cupidatat commodo. Elit sunt amet fugiat veniam occaecat fugiat aliqua.</p>
            </div>
        </div>


        <!-- Formular -->

        <form class="mt-4">
            <div class="space-y-12 sm:space-y-16">

                <!-- erste Seite -->

                <div>
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Allgemeine Angaben</h2>
                    <p class="max-w-2xl mt-1 text-sm leading-6 text-gray-600">This information will be displayed publicly so be careful what you share.</p>

                    <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">
                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="project-name" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Projektname</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="project-name" id="project-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <!--div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="username" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Username</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <div class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600 sm:max-w-md">
                                <span class="flex items-center pl-3 text-gray-500 select-none sm:text-sm">workcation.com/</span>
                                <input type="text" name="username" id="username" autocomplete="username" class="block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6" placeholder="janesmith">
                            </div>
                            </div>
                        </div-->

                        <!--div class="sm:grid sm:grid-cols-3 sm:items-center sm:gap-4 sm:py-6">
                            <label for="photo" class="block text-sm font-medium leading-6 text-gray-900">Photo</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <div class="flex items-center gap-x-3">
                                <svg class="w-12 h-12 text-gray-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.486 7.486 0 0112 15a7.486 7.486 0 015.855 2.812A8.224 8.224 0 0112 20.25a8.224 8.224 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd" />
                                </svg>
                                <button type="button" class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Change</button>
                            </div>
                            </div>
                        </div-->

                        <!--div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="cover-photo" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Cover photo</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <div class="flex justify-center max-w-2xl px-6 py-10 border border-dashed rounded-lg border-gray-900/25">
                                <div class="text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" />
                                </svg>
                                <div class="flex mt-4 text-sm leading-6 text-gray-600">
                                    <label for="file-upload" class="relative font-semibold text-indigo-600 bg-white rounded-md cursor-pointer focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                                    <span>Upload a file</span>
                                    <input id="file-upload" name="file-upload" type="file" class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs leading-5 text-gray-600">PNG, JPG, GIF up to 10MB</p>
                                </div>
                            </div>
                            </div>
                        </div-->

                    <!-- Date picker -->

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="project-start-date" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Projektstart</label></label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <div class="flex -space-x-px">
                                    <div class="flex-1 w-1/2 min-w-0">
                                        <!--label for="project-start-date" class="sr-only">Startdatum</label-->
                                        <input type="date" name="project-start-date" id="project-start-date" class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
                                    </div>
                                </div>
                            </div>

                            <label for="project-start-date" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Projektende</label></label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <div class="flex -space-x-px">
                                    <div class="flex-1 w-1/2 min-w-0">
                                        <!--label for="project-end-date" class="sr-only">Enddatum</label-->
                                        <input type="date" name="project-end-date" id="project-end-date" class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- WYSIWYG Editor -->

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="about" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Projektbeschreibung</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <textarea id="about" name="about" rows="3" class="block w-full max-w-2xl rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea>
                            <!--p class="mt-3 text-sm leading-6 text-gray-600">Write a few sentences about yourself.</p-->
                            </div>
                        </div>

                    <!-- TODO: File Upload -->

                    <!-- Checkbox List -->

                        <fieldset>
                            <legend class="sr-only">Aufgaben der Studierendenschaft</legend>
                            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:py-6">
                                <div class="text-sm font-semibold leading-6 text-gray-900" aria-hidden="true">Aufgaben der Studierendenschaft</div>
                                <div class="mt-4 sm:col-span-2 sm:mt-0">
                                    <div class="max-w-lg space-y-6">
                                        <div class="relative flex gap-x-3">
                                            <div class="flex items-center h-6">
                                            <input id="comments" name="comments" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                            </div>
                                            <div class="text-sm leading-6">
                                                <label for="comments" class="font-medium text-gray-900">Meinungsbildung</label>
                                                <p class="mt-1 text-gray-600">
                                                    die Meinungsbildung in der Gruppe der Studierenden zu ermöglichen
                                                </p>
                                            </div>
                                        </div>
                                        <div class="relative flex gap-x-3">
                                            <div class="flex items-center h-6">
                                            <input id="candidates" name="candidates" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                            </div>
                                            <div class="text-sm leading-6">
                                                <label for="candidates" class="font-medium text-gray-900">Hochschule und Gesellschaft</label>
                                                <p class="mt-1 text-gray-600">
                                                    die Belange ihrer Mitglieder in Hochschule und Gesellschaft wahrzunehmen
                                                </p>
                                            </div>
                                        </div>
                                        <div class="relative flex gap-x-3">
                                            <div class="flex items-center h-6">
                                                <input id="offers" name="offers" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                            </div>
                                            <div class="text-sm leading-6">
                                                <label for="offers" class="font-medium text-gray-900">Erfüllung der Aufgaben der Hochschule</label>
                                                <p class="mt-1 text-gray-600">
                                                    an der Erfüllung der Aufgaben der Hochschule (§§ 3 und 4) insbesondere durch Stellungnahmen zu hochschul- oder wissenschaftspolitischen Fragen mitzuwirken
                                                </p>
                                            </div>
                                        </div>
                                        <div class="relative flex gap-x-3">
                                            <div class="flex items-center h-6">
                                                <input id="offers" name="offers" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                            </div>
                                            <div class="text-sm leading-6">
                                                <label for="offers" class="font-medium text-gray-900">politische Bildung</label>
                                                <p class="mt-1 text-gray-600">
                                                    auf der Grundlage der verfassungsmäßigen Ordnung die politische Bildung, das staatsbürgerliche Verantwortungsbewusstsein und die Bereitschaft ihrer Mitglieder zur aktiven Toleranz sowie zum Eintreten für die Grund- und Menschenrechte zu fördern
                                                </p>
                                            </div>
                                        </div>
                                        <div class="relative flex gap-x-3">
                                            <div class="flex items-center h-6">
                                                <input id="offers" name="offers" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                            </div>
                                            <div class="text-sm leading-6">
                                                <label for="offers" class="font-medium text-gray-900">Kultur & Soziales</label>
                                                <p class="mt-1 text-gray-600">
                                                    kulturelle, fachliche, wirtschaftliche und soziale Belange ihrer Mitglieder wahrzunehmen
                                                </p>
                                            </div>
                                        </div>
                                        <div class="relative flex gap-x-3">
                                            <div class="flex items-center h-6">
                                                <input id="offers" name="offers" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                            </div>
                                            <div class="text-sm leading-6">
                                                <label for="offers" class="font-medium text-gray-900">Integration ausländischer Studierender</label>
                                                <p class="mt-1 text-gray-600">
                                                    die Integration ausländischer Studierender zu fördern
                                                </p>
                                            </div>
                                        </div>
                                        <div class="relative flex gap-x-3">
                                            <div class="flex items-center h-6">
                                                <input id="offers" name="offers" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                            </div>
                                            <div class="text-sm leading-6">
                                                <label for="offers" class="font-medium text-gray-900">Sport</label>
                                                <p class="mt-1 text-gray-600">
                                                    den Studentensport zu fördern
                                                </p>
                                            </div>
                                        </div>
                                        <div class="relative flex gap-x-3">
                                            <div class="flex items-center h-6">
                                                <input id="offers" name="offers" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                            </div>
                                            <div class="text-sm leading-6">
                                                <label for="offers" class="font-medium text-gray-900">Internationales</label>
                                                <p class="mt-1 text-gray-600">
                                                    die überregionalen und internationalen Studierendenbeziehungen zu pflegen
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                    <!-- Sonstige Eingabefelder -->

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="target-group" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Zielgruppe</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="target-group" id="taret-group" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-2xl sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="estimated-guests" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Erwartete Teilnehmendenzahl</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="estimated-guests" id="estimated-guests" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-2xl sm:text-sm sm:leading-6">
                            </div>
                            <label for="estimated-students" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Davon Studierende</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="estimated-guests" id="estimated-guests" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-2xl sm:text-sm sm:leading-6">
                            </div>
                        </div>

                    <!-- Buttons -->

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <div></div>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <button type="button" class="inline-flex justify-center px-3 py-2 text-sm font-semibold leading-6 text-gray-900">Zurück</button>
                                <button type="submit" class="inline-flex justify-center px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Weiter</button>
                            </div>
                        </div>


                    </div>

                </div>

                <!-- zweite Seite -->

                <div>
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Finanzplan</h2>
                    <p class="max-w-2xl mt-1 text-sm leading-6 text-gray-600">Use a permanent address where you can receive mail.</p>

                    <!-- Toggle Vorsteuerabzugsberechtigung -->

                    <!-- Ausgaben -->
                    Tabelle

                    <!-- Einnahmen -->
                    Tabelle

                    <!-- Saldo -->
                    Input disabled

                    <!-- Fileupload -->
                    <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">
                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="first-name" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">First name</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="first-name" id="first-name" autocomplete="given-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-xs sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="last-name" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Last name</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="last-name" id="last-name" autocomplete="family-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-xs sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="email" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Email address</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input id="email" name="email" type="email" autocomplete="email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-md sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="country" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Country</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <select id="country" name="country" autocomplete="country-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-xs sm:text-sm sm:leading-6">
                                    <option>United States</option>
                                    <option>Canada</option>
                                    <option>Mexico</option>
                                </select>
                            </div>
                        </div>

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="street-address" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Street address</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="street-address" id="street-address" autocomplete="street-address" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-xl sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="city" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">City</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="city" id="city" autocomplete="address-level2" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-xs sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="region" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">State / Province</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="region" id="region" autocomplete="address-level1" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-xs sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
                            <label for="postal-code" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">ZIP / Postal code</label>
                            <div class="mt-2 sm:col-span-2 sm:mt-0">
                                <input type="text" name="postal-code" id="postal-code" autocomplete="postal-code" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-xs sm:text-sm sm:leading-6">
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Notifications</h2>
                    <p class="max-w-2xl mt-1 text-sm leading-6 text-gray-600">We'll always let you know about important changes, but you pick what else you want to hear about.</p>

                    <div class="pb-12 mt-10 space-y-10 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">
                    <fieldset>
                        <legend class="sr-only">By Email</legend>
                        <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:py-6">
                        <div class="text-sm font-semibold leading-6 text-gray-900" aria-hidden="true">By Email</div>
                        <div class="mt-4 sm:col-span-2 sm:mt-0">
                            <div class="max-w-lg space-y-6">
                            <div class="relative flex gap-x-3">
                                <div class="flex items-center h-6">
                                <input id="comments" name="comments" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                </div>
                                <div class="text-sm leading-6">
                                <label for="comments" class="font-medium text-gray-900">Comments</label>
                                <p class="mt-1 text-gray-600">Get notified when someones posts a comment on a posting.</p>
                                </div>
                            </div>
                            <div class="relative flex gap-x-3">
                                <div class="flex items-center h-6">
                                <input id="candidates" name="candidates" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                </div>
                                <div class="text-sm leading-6">
                                <label for="candidates" class="font-medium text-gray-900">Candidates</label>
                                <p class="mt-1 text-gray-600">Get notified when a candidate applies for a job.</p>
                                </div>
                            </div>
                            <div class="relative flex gap-x-3">
                                <div class="flex items-center h-6">
                                <input id="offers" name="offers" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600">
                                </div>
                                <div class="text-sm leading-6">
                                <label for="offers" class="font-medium text-gray-900">Offers</label>
                                <p class="mt-1 text-gray-600">Get notified when a candidate accepts or rejects an offer.</p>
                                </div>
                            </div>
                            </div>
                        </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class="sr-only">Push Notifications</legend>
                        <div class="sm:grid sm:grid-cols-3 sm:items-baseline sm:gap-4 sm:py-6">
                        <div class="text-sm font-semibold leading-6 text-gray-900" aria-hidden="true">Push Notifications</div>
                        <div class="mt-1 sm:col-span-2 sm:mt-0">
                            <div class="max-w-lg">
                            <p class="text-sm leading-6 text-gray-600">These are delivered via SMS to your mobile phone.</p>
                            <div class="mt-6 space-y-6">
                                <div class="flex items-center gap-x-3">
                                <input id="push-everything" name="push-notifications" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-600">
                                <label for="push-everything" class="block text-sm font-medium leading-6 text-gray-900">Everything</label>
                                </div>
                                <div class="flex items-center gap-x-3">
                                <input id="push-email" name="push-notifications" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-600">
                                <label for="push-email" class="block text-sm font-medium leading-6 text-gray-900">Same as email</label>
                                </div>
                                <div class="flex items-center gap-x-3">
                                <input id="push-nothing" name="push-notifications" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-600">
                                <label for="push-nothing" class="block text-sm font-medium leading-6 text-gray-900">No push notifications</label>
                                </div>
                            </div>
                            </div>
                        </div>
                        </div>
                    </fieldset>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end mt-6 gap-x-6">
                <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancel</button>
                <button type="submit" class="inline-flex justify-center px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save</button>
            </div>
        </form>

<!--x-heroicon-o-plus class="-ml-0.5 mr-0.5 h-4 w-4"/-->



            <form method="POST" enctype="multipart/form-data" id="upload" action="#" >
                @csrf
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                    </div>
                    <div class="col-md-6 offset-md-3">
                    </div>
                    <div class="py-4">
                    </div>
                    <br>
                    <div class="col-md-6 offset-md-3">
                    </div>
                </div>
            </form>
    </div>
</x-layout>
