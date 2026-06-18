<?php

namespace App\Http\Controllers\Legacy;

use App\Exceptions\LegacyJsonException;
use App\Exceptions\LegacyRedirectException;
use App\Http\Controllers\Controller;
use App\Models\Legacy\FileInfo;
use forms\projekte\auslagen\AuslagenHandler2;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyController extends Controller
{
    public function bootstrap(): void
    {
        require_once base_path('legacy/lib/inc.all.php');
    }

    public function render(Request $request)
    {
        try {
            ob_start();
            $this->bootstrap();
            require base_path('legacy/www/index.php');
            $output = ob_get_clean();

            // if wanted by the unit test the content is delivered without the layout
            if ($request->input('testing')) {
                return $output;
            }

            // otherwise with
            return view('legacy.main', [
                'content' => $output,
                // 'sectionTabs' => $this->resolveSectionTabs($request),
            ]);
        } catch (LegacyRedirectException $e) {
            return $e->redirect;
        } catch (LegacyJsonException $e) {
            ob_get_clean(); // throw away all output

            return response()->json($e->content);
        } catch (\Exception $exception) {
            // get rid of the already printed html
            ob_get_clean();
            throw $exception;
        }
    }

    /**
     * The section sub-navigation tabs (Übersicht/TODO/Buchungen) used to be rendered
     * inside the legacy iframe; they are now lifted into the Laravel shell's tab bar.
     * Returns a list of [label, icon, href, active] tabs for the current route, or
     * null when the page has no tabs (e.g. Konto, which keeps its own iframe tabs).
     *
     * @return array<int, array{label: string, icon: string, href: string, active: bool}>|null
     *
     * @phpstan-ignore-next-line
     */
    private function resolveSectionTabs(Request $request): ?array
    {
        if ($request->routeIs('legacy.dashboard')) {
            $hhp = $request->route('hhp_id');
            $sub = $request->route('sub');

            return collect([
                'mygremium' => ['Meine Gremien', 'home'],
                'allgremium' => ['Alle Gremien', 'globe-alt'],
                'open-projects' => ['Offene Projekte', 'document-text'],
            ])->map(fn ($v, $key) => [
                'label' => $v[0],
                'icon' => $v[1],
                'href' => route('legacy.dashboard', $hhp ? ['hhp_id' => $hhp, 'sub' => $key] : ['sub' => $key]),
                'active' => $sub === $key,
            ])->values()->all();
        }

        if ($request->routeIs('legacy.todo.*')) {
            $items = [
                ['Belege fehlen', 'folder-open', 'legacy.todo.belege'],
                ['Haushaltsverantwortliche*r', 'scale', 'legacy.todo.hv'],
                ['Kassenverantwortliche*r', 'calculator', 'legacy.todo.kv'],
            ];
            if (\Auth::user()->can('finance', \Auth::user())) {
                $items[] = ['Überweisungen', 'banknotes', 'legacy.todo.kv.bank'];
            }

            return array_map(fn ($t) => [
                'label' => $t[0],
                'icon' => $t[1],
                'href' => route($t[2]),
                'active' => $request->routeIs($t[2]),
            ], $items);
        }

        if ($request->routeIs('legacy.booking') || $request->routeIs('legacy.booking.*')) {
            // The sidebar links to the plain /booking route, which carries no hhp_id;
            // the legacy renderer then defaults to the newest "final" budget plan
            // (see Renderer::renderHHPSelector) and shows the "instruct" tab.
            $hhp = $request->route('hhp_id')
                ?? LegacyBudgetPlan::where('state', 'final')->orderByDesc('von')->value('id');

            if (! $hhp) {
                return null;
            }

            $active = match (true) {
                $request->routeIs('legacy.booking.text') => 'text',
                $request->routeIs('legacy.booking.history') => 'history',
                default => 'instruct',
            };

            $items = [
                ['Anweisen', 'scale', 'legacy.booking.instruct', 'instruct'],
                ['Durchführen', 'document-text', 'legacy.booking.text', 'text'],
                ['Historie', 'clock', 'legacy.booking.history', 'history'],
            ];

            return array_map(fn ($t) => [
                'label' => $t[0],
                'icon' => $t[1],
                'href' => route($t[2], ['hhp_id' => $hhp]),
                'active' => $active === $t[3],
            ], $items);
        }

        if ($request->routeIs('legacy.konto')) {
            // One tab per bank account/cash register, plus a trailing "+" tab linking
            // to the new-account form. Defaults mirror the legacy handler: newest
            // "final" plan and account id 1.
            $hhp = $request->route('hhp_id')
                ?? LegacyBudgetPlan::where('state', 'final')->orderByDesc('von')->value('id');

            if (! $hhp) {
                return null;
            }

            $activeKonto = (int) ($request->route('konto_id') ?? 1);

            $tabs = BankAccount::orderBy('id')->get()->map(fn ($konto) => [
                'label' => $konto->name,
                'icon' => $konto->manually_enterable ? 'banknotes' : 'credit-card',
                'href' => route('legacy.konto', ['hhp_id' => $hhp, 'konto_id' => $konto->id]),
                'active' => $konto->id === $activeKonto,
            ])->all();

            $tabs[] = [
                'label' => '',
                'icon' => 'plus',
                'href' => route('bank-account.new'),
                'active' => false,
            ];

            return $tabs;
        }

        return null;
    }

    public function renderFile($auslagen_id, $beleg_id, $hash)
    {
        $finfo = FileInfo::where(['hashname' => $hash, 'link' => $beleg_id])->firstOrFail();
        $name = $finfo->filename ?? 'error';
        $path = "/auslagen/$auslagen_id/$hash/$name.pdf";

        return view('components.inlineFile', ['src' => $path]);
    }

    public function belegePdf(int $project_id, int $auslagen_id, int $version, ?string $file_name = null)
    {
        // file was generated and requested by the iframe
        if ($file_name !== null) {
            return \Storage::response(
                "auslagen/$auslagen_id/belege-pdf-v$version.pdf",
                $file_name
            );
        }
        // generate file and iframe to display it
        $this->bootstrap();
        $ah = new AuslagenHandler2([
            'pid' => $project_id,
            'aid' => $auslagen_id,
            'action' => 'belege-pdf',
        ]);
        $ah->generate_belege_pdf();
        $path = route('legacy.belege-pdf', [
            'projekt_id' => $project_id,
            'auslagen_id' => $auslagen_id,
            'version' => $version,
            'file_name' => "Belege-IP$project_id-A$auslagen_id.pdf",
        ]);

        return view('components.inlineFile', ['src' => $path]);
    }

    public function zahlungsanweisungPdf(int $project_id, int $auslagen_id, int $version, ?string $file_name = null)
    {
        // file was generated and requested by the iframe call
        if ($file_name !== null) {
            return \Storage::response(
                "/auslagen/$auslagen_id/zahlungsanweisung-v$version.pdf",
                $file_name
            );
        }
        // generate file and iframe to request it
        $this->bootstrap();
        $ah = new AuslagenHandler2([
            'pid' => $project_id,
            'aid' => $auslagen_id,
            'action' => 'zahlungsanweisung-pdf',
        ]);
        $ah->generate_zahlungsanweisung_pdf();
        $path = route('legacy.zahlungsanweisung-pdf', [
            'projekt_id' => $project_id,
            'auslagen_id' => $auslagen_id,
            'version' => $version,
            'file_name' => "Zahlungsanweisung-IP$project_id-A$auslagen_id.pdf",
        ]);

        return view('components.inlineFile', ['src' => $path]);
    }

    public function deliverFile($auslagen_id, $fileHash, $fileName): StreamedResponse
    {
        $path = "/auslagen/$auslagen_id/$fileHash.pdf";
        if (\Storage::exists($path)) {
            return \Storage::response($path, $fileName);
        }
        throw new FileNotFoundException("Datei $path konnte nicht gefunden werden");
    }
}
