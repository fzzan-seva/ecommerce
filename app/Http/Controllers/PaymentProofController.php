<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PaymentProofController extends Controller
{
    /**
     * Serve a payment proof to its owner (or an admin).
     *
     * The proof used to be linked as asset('storage/'.$path), which made every
     * uploaded transfer receipt world-readable to anyone who guessed the URL.
     * Serving it through here keeps the file behind the auth middleware.
     */
    public function show(Request $request, Order $order): Response
    {
        $user = $request->user();

        abort_unless($user && ($order->user_id === $user->id || $user->isAdmin()), 403);

        if (! $order->payment_proof) {
            abort(404);
        }

        // Private disk: this path is outside the public/storage symlink, so the
        // file is not reachable by URL no matter what the filename is.
        $disk = Storage::disk('payment_proofs');
        $path = $order->payment_proof;

        // Never let a tampered DB value escape the payment-proofs directory.
        $real = realpath($disk->path($path));
        $base = realpath($disk->path('payment-proofs'));

        abort_unless($real !== false && $base !== false && str_starts_with($real, $base.DIRECTORY_SEPARATOR), 404);
        abort_unless(is_file($real), 404);

        // Derive the type from the bytes actually on disk. The upload rule only
        // whitelists jpg/jpeg/png, but sniffing beats trusting the stored
        // extension, and the whitelist stops us announcing text/html or
        // application/x-httpd-php here.
        $mime = @mime_content_type($real) ?: 'application/octet-stream';
        abort_unless(str_starts_with($mime, 'image/'), 404);
        $extension = $mime === 'image/jpeg' ? 'jpg' : 'png';

        $response = new BinaryFileResponse($real);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Disposition', "inline; filename=\"bukti-pembayaran.{$extension}\"");
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
