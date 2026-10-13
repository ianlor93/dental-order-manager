<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\{Auth, Log};
use App\Support\StudioCompliance;

class WelcomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 🔒 GATE LOBBY `/` (4c-③b): utente in-scope ma NON conforme → onboarding,
        // PRIMA di mostrare la lobby. Stessa nozione del gate NC e di EnsureLegalCompliance
        // (StudioCompliance::isApplicable + isCompliant). Safeguard: non-staff senza studio
        // e senza lead → isApplicable=false → lobby normale (niente dead-end). Target =
        // legal.onboarding (route sibling, fuori da questo gate → nessun loop).
        if ($user && StudioCompliance::isApplicable($user)
                 && !StudioCompliance::isCompliant(StudioCompliance::resolveStudio($user))) {
            return redirect()->route('legal.onboarding', ['user' => $user->uid]);
        }

        // Parsing delle News (Percorso Assoluto)
        $news = $this->parseNewsFile();

        return view('welcome', compact('user', 'news'));
    }

    private function parseNewsFile()
    {
        $path = storage_path('app/system_news.txt');

        if (!file_exists($path)) {
            Log::warning("LOBBY: File news non trovato in: " . $path);
            return [];
        }

        try {
            $content = file_get_contents($path);
            $rawBlocks = explode('---', $content);
            $parsedNews = [];

            foreach ($rawBlocks as $block) {
                $block = trim($block);
                if (empty($block)) continue;

                $item = ['date' => '', 'type' => 'info', 'title' => '', 'body' => ''];
                $lines = explode("\n", $block);

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || str_starts_with($line, '#')) continue;

                    if (preg_match('/^(DATE|TYPE|TITLE|BODY)\s*:\s*(.*)$/i', $line, $matches)) {
                        $key = strtoupper($matches[1]);
                        $value = $matches[2];
                        $item[strtolower($key)] = $value;
                    } else {
                        $item['body'] .= " " . $line;
                    }
                }

                if (!empty($item['title'])) {
                    $parsedNews[] = $item;
                }
            }
            return $parsedNews;
        } catch (\Exception $e) {
            Log::error("LOBBY-PARSER-ERROR: " . $e->getMessage());
            return [];
        }
    }
}
