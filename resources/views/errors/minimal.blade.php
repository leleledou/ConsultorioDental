{{-- Respaldo para el resto de códigos HTTP (401, 429, 503…): mensaje genérico, sin detalles de la excepción. --}}
@php $codigoHttp = isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500; @endphp
<x-error-pagina :codigo="$codigoHttp" titulo="No se pudo completar la solicitud" mensaje="Inténtelo de nuevo en unos minutos. Si el problema continúa, avise al administrador." />
