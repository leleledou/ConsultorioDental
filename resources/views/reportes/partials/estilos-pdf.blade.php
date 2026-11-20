{{-- Estilos de los documentos por paciente en PDF (RF-84 y RF-85): los mismos de reportes/pdf más el odontograma. --}}
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111827; }
    h1 { font-size: 14px; margin: 0; }
    h2 { font-size: 12px; margin: 8px 0 2px; }
    h3 { font-size: 11px; margin: 14px 0 4px; padding-bottom: 2px; border-bottom: 1px solid #9ca3af; }
    p { margin: 2px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th, td { border: 1px solid #9ca3af; padding: 4px; text-align: left; vertical-align: top; }
    th { background-color: #e5e7eb; }
    .numero { text-align: right; white-space: nowrap; }
    tr.detalle td { font-size: 9px; color: #4b5563; padding-left: 14px; }
    .arcada { margin-top: 6px; text-align: center; font-size: 8px; color: #4b5563; text-transform: uppercase; }
    table.odontograma { width: auto; margin: 2px auto 0; border-collapse: separate; border-spacing: 2px; }
    table.odontograma td { border: 1px solid #9ca3af; padding: 0; width: 20px; height: 18px; text-align: center; vertical-align: middle; font-size: 8px; font-weight: bold; }
    table.odontograma td.vacia { border-style: dashed; color: #9ca3af; font-weight: normal; }
    table.odontograma td.linea-media { border: none; border-left: 1px solid #4b5563; width: 4px; }
    .leyenda { margin-top: 6px; text-align: center; font-size: 8px; }
    .muestra { display: inline-block; width: 8px; height: 8px; border: 1px solid #9ca3af; }
    .lado { width: 50%; vertical-align: top; border: none; padding: 0 4px; }
</style>
