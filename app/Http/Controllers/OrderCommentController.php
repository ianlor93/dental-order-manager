<?php

namespace App\Http\Controllers;

use App\Models\{Order, OrderComment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log};
use Exception;

class OrderCommentController extends Controller
{
    /**
     * Carica i commenti associati a un ordine.
     * Utilizza lo scope 'visibleTo' per filtrare i messaggi interni.
     */
    public function index(Order $order)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 🛡️ Sicurezza: Solo lo staff o il proprietario dell'ordine può vedere i commenti
        if (!$user->isStaff() && $user->uid !== $order->user_id) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        // Recuperiamo i commenti con l'autore (User), filtrati per visibilità
        $comments = $order->comments()
            ->with('user')
            ->visibleTo($user)
            ->oldest()
            ->get()
            ->map(function ($comment) {
                // 🤖 LOGICA BOT/SISTEMA: Protezione se l'utente è null
                $authorName = $comment->user ? ($comment->user->displayname ?? $comment->user->uid) : 'Sistema';
                $isStaff = $comment->user ? $comment->user->isStaff() : false;

                return [
                    'id'          => $comment->id,
                    'body'        => $comment->body,
                    'author'      => $authorName,
                    'is_internal' => $comment->is_internal,
                    'created_at'  => $comment->created_at->format('d/m/Y H:i'),
                    'is_staff'    => $isStaff,
                    'is_system'   => is_null($comment->user_uid), // Flag per la grafica del Bot
                ];
            });

        return response()->json($comments);
    }

    /**
     * Salva un nuovo commento.
     */
    public function store(Request $request, Order $order)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        // 🛡️ Sicurezza: Solo lo staff o il proprietario dell'ordine può commentare
        if (!$user->isStaff() && $user->uid !== $order->user_id) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        try {
            // Logica is_internal:
            // Se è staff, prende il valore dal checkbox/toggle.
            // Se è cliente, è SEMPRE false (pubblico).
            $isInternal = $user->isStaff() ? $request->boolean('is_internal') : false;

            $comment = OrderComment::create([
                'order_id'    => $order->id,
                'user_uid'    => $user->uid,
                'body'        => $request->body,
                'is_internal' => $isInternal,
            ]);

            Log::info("MDR-LOG: Nuovo commento creato per ordine [{$order->code}] da [{$user->uid}]");

            return response()->json([
                'success'    => true,
                'comment'    => [
                    'author'      => $user->displayname ?? $user->uid,
                    'body'        => $comment->body,
                    'is_internal' => $comment->is_internal,
                    'created_at'  => $comment->created_at->format('d/m/Y H:i'),
                ]
            ]);

        } catch (Exception $e) {
            Log::error("MDR-LOG-ERROR: Impossibile salvare il commento: " . $e->getMessage());
            return response()->json(['error' => 'Errore durante il salvataggio'], 500);
        }
    }
}