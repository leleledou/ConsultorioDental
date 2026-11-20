{{-- Encabezado del consultorio común a todos los PDF (CU45, RF-84 y RF-85). Recibe $titulo y $detalle. --}}
<h1>Consultorio Odontológico DentalRox</h1>
<h2>{{ $titulo }}</h2>
<p>{{ $detalle }}</p>
<p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>
