<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use App\Models\PagoConsulta;
use App\Services\Pagos\ManualQrPayment;
use App\Services\Pagos\PaymentManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QrPagoController extends Controller
{
    public function index(Request $request, ManualQrPayment $manual): View
    {
        abort_unless($request->user()->tienePermiso('citas.gestionar'), 403);
        $pagos = PagoConsulta::query()->with(['cita.especialidad', 'cita.paciente'])
            ->where('estado', 'qr_revision')->orderBy('reportado_en')->paginate(15);

        return view('pagos.qr_revision', ['pagos' => $pagos, 'qr' => $manual->activeQr()]);
    }

    public function upload(Request $request, ManualQrPayment $manual): RedirectResponse
    {
        abort_unless($request->user()->tienePermiso('usuarios.gestionar'), 403);
        $data = $request->validate(['qr' => ['required', 'file', 'max:2048', 'mimes:png,jpg,jpeg']]);
        $manual->saveQr($request->user(), $data['qr']);

        return redirect()->route('pagos.qr.index')->with('status', 'QR actualizado. Los pagos ya iniciados conservan su QR original.');
    }

    public function image(Request $request, PagoConsulta $pago, PaymentManagement $payments): Response
    {
        $payments->authorize($request->user(), $pago);
        abort_unless($pago->metodo === 'qr' && $pago->qr_id, 404);
        $qr = DB::table('codigos_qr_pago')->where('id', $pago->qr_id)->first();
        abort_unless($qr, 404);

        return response(base64_decode($qr->imagen_base64, true), 200, ['Content-Type' => $qr->mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function select(Request $request, PagoConsulta $pago, ManualQrPayment $manual): RedirectResponse
    {
        $manual->select($request->user(), $pago);

        return redirect()->route('pagos.show', $pago);
    }

    public function report(Request $request, PagoConsulta $pago, ManualQrPayment $manual): RedirectResponse
    {
        $data = $request->validate(['referencia' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9 ._\\/-]*$/']]);
        $manual->report($request->user(), $pago, $data['referencia']);

        return redirect()->route('pagos.show', $pago)->with('status', 'Reporte enviado a recepción. La cita aún no está confirmada.');
    }

    public function decide(Request $request, PagoConsulta $pago, ManualQrPayment $manual): RedirectResponse
    {
        abort_unless($request->user()->tienePermiso('citas.gestionar'), 403);
        $data = $request->validate(['decision' => ['required', 'in:aprobar,rechazar'], 'motivo' => ['nullable', 'string', 'max:500', 'required_if:decision,rechazar']]);
        $manual->decide($request->user(), $pago, $data['decision'] === 'aprobar', $data['motivo'] ?? null);

        $message = $pago->fresh()->estado === 'revision'
            ? 'Pago confirmado, pero la cita ya no está disponible. Revisa la devolución con el paciente.'
            : 'La revisión fue registrada.';

        return redirect()->route('pagos.qr.index')->with('status', $message);
    }
}
