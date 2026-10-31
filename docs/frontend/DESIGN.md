# DESIGN — Identidad visual de DentalRox

Estado: **fases 1 a 3, consolidación visual y Atención Clínica (ciclo 3) implementadas**. El login y el panel son la **referencia estética definitiva** del resto de la plataforma: los demás módulos deben igualarlos (mismos tokens, tipografías, radios, sombras y tratamiento del logo). Los valores definitivos viven en `tailwind.config.js` y `resources/css/app.css`.

## Concepto
Odontología moderna, limpia y de confianza: *clínico sereno, preciso, luminoso*. Mucho blanco, turquesa como acento de marca y gris como apoyo.

## Fuentes de verdad
- Logo: `docs/branding/Logotipo dental DENTALROX minimalista.png`
- Funcionalidad y prototipos: `docs/proyecto/CONSULTORIO DENTALROX CICLO#2 REVISION DOCUMENTO.pdf`
- Requisitos que afectan al diseño: RNF-15 (responsivo), RNF-23 (español), RNF-24 (tareas frecuentes en máximo 3 acciones desde el panel), RNF-25 (errores claros), RNF-26 (odontograma por color).

## El logo
- Muela estilizada: corona en **gris** con un reflejo gris claro, y una curva/sonrisa y raíces en **turquesa**. A la derecha, el texto **DENTAL** en turquesa y **ROX** en gris, en mayúsculas gruesas y geométricas.
- Proporción: 1983 × 793 px (≈ 2,5 : 1). El isotipo ocupa aprox. el tercio izquierdo; el texto, los dos tercios restantes.
- El PNG tiene **fondo blanco opaco**. Solo se ve bien sobre blanco. Para usarlo sobre otros fondos hará falta una versión transparente del mismo logo (recorte del original, no un rediseño); queda pendiente de aprobación.
- Reglas: no deformar, no recolorear, no añadir sombras ni efectos; dejar alrededor un margen mínimo igual a la altura de la «D». En la navegación, altura mínima ≈ 32 px; en el login, ancho ≈ 240–320 px.
- Uso previsto: pantalla de login y navegación principal. Copiarlo a `public/images/` cuando comience la implementación.

## Colores del logo (oficiales, medidos del PNG)

| Token | Valor | Uso en el logo |
|---|---|---|
| `logo-turquesa` | `#00B8C0` | DENTAL, sonrisa y raíces |
| `logo-gris` | `#909191` | ROX y corona |
| `logo-gris-claro` | `#D2D2D2` | reflejo de la corona |
| `logo-blanco` | `#FFFFFF` | fondo |

## Colores complementarios (propuestos, no oficiales)
El turquesa `#00B8C0` sobre blanco tiene contraste ≈ 2,4 : 1: **no sirve para texto ni para botones con texto blanco**. Por eso se derivan tonos de la misma familia. Todos deben validarse con contraste WCAG AA durante la implementación.

| Token | Valor | Uso |
|---|---|---|
| `brand-50` | `#E6F8F9` | fondos suaves, filas activas |
| `brand-100` | `#BFEFF1` | bordes y badges suaves |
| `brand-500` | `#00B8C0` | = turquesa del logo; decoración, iconos, indicadores |
| `brand-600` | `#008F96` | hover |
| `brand-700` | `#007F86` | botones primarios y enlaces (≈ 4,8 : 1 con blanco) |
| `brand-900` | `#0B3C40` | sidebar o encabezados oscuros, tema oscuro |
| `ink` | `#1F2933` | texto principal |
| `ink-soft` | `#52606D` | texto secundario (el gris del logo no cumple para texto) |
| `surface` | `#F6F9FA` | fondo de página |
| `line` | `#E1E8EB` | bordes |
| Estados | verde `#2F8F5B`, ámbar `#B7791F`, rojo `#C0392B`, azul `#2B6CB0` | siempre con texto o icono, nunca solo color. Compatibles con los estados de cita actuales (programada, atendida, cancelada, no asistida) |

## Tipografía (aprobada)
- Texto e interfaz: **Figtree** (ya cargada en el proyecto).
- Titulares: **Plus Jakarta Sans** (600–800), cargada desde fonts.bunny.net junto con Figtree.

## Principios de componentes
- Blanco dominante, tarjetas solo para unidades reales; bordes finos `line` y sombra mínima.
- Radios 8 px (controles) y 12 px (tarjetas). Botón primario `brand-700`; secundario con borde.
- Foco visible en turquesa. Movimiento breve (150–200 ms) y respeto de `prefers-reduced-motion`.
- Tablas legibles y compactas; en móvil se reorganizan, no se encogen.
- Iconos: SVG inline con un mismo trazo, sin librerías nuevas.

## Decisiones tomadas (fase 1)
- **Navegación:** sidebar lateral blanca con los 5 paquetes (P1–P5) como grupos plegables; en móvil es un cajón deslizante. Cabecera superior con contexto (paquete actual y fecha) y menú de usuario. Las opciones salen de `MenuService`.
- **Logo:** `public/images/dentalrox-logo.png` (copia del original de `docs/branding/`), siempre sobre fondo blanco. Se aplica `brightness(1.012)` para que su blanco (254) coincida con el blanco puro de la página.
- **Tema oscuro:** desactivado (`darkMode: 'class'` sin aplicar la clase). Las variantes `dark:` de los módulos quedan inertes.
- **Tokens:** en `tailwind.config.js` (`brand`, `ink`, `surface`, `line`, `marca`, sombras `card`/`pop`) y componentes en `resources/css/app.css` (`.dr-card`, `.nav-item`, `.page-header`). Mientras no se rediseñen los módulos, la paleta `indigo` de Tailwind está remapeada a la familia turquesa.
- **Iconos:** componente `<x-icon name="…">` con SVG en línea.

## Fase 2: módulo Pacientes (implementado)
Patrón a reutilizar en los demás módulos:
- **Listado:** `.dr-card` con barra de búsqueda y acción principal arriba, contador real, tabla en `md+` (cabecera `surface`, filas con hover `brand-50`, avatar de iniciales) y **lista de tarjetas en móvil**. Estado vacío con icono y texto. Paginación propia en `resources/views/vendor/pagination/tailwind.blade.php` (afecta a todos los listados con `->links()`).
- **Formulario:** panel en la misma página, agrupado con `<fieldset>` (Datos personales, Contacto, Observaciones), `*` en los obligatorios, errores junto al campo (borde rojo + `aria-invalid`) y aviso resumen. Botones apilados en móvil.
- **Ficha (`show`):** resumen con avatar, datos en `dl` con etiquetas en mayúsculas, acciones al pie.
- **Componentes Blade:** `x-alerta`, `x-campo`, `x-insignia-activo`, `x-pacientes.acciones`, `x-text-input` (prop `error`), `x-danger-button` (prop `suave`). Clases CSS: `.btn`, `.btn-sm`, `.btn-primary|secondary|danger|danger-soft`, `.dr-input`, `.dr-input-error`, `.badge`.
- **Acciones destructivas:** Inhabilitar usa el botón rojo suave y conserva el `confirm()` nativo.

## Fase 3: Citas y Agenda (implementado)
- **Estados de cita** (`<x-estado-cita>`): programada = turquesa de marca, atendida = verde, cancelada = rojo (texto tachado en agenda), no asistida = ámbar. Siempre insignia con punto y texto; la agenda usa el mismo código de color en el borde y fondo de cada cita.
- **Agenda:** barra de navegación (anterior / Hoy / siguiente), selector Día|Semana (segmentado), filtros y leyenda de estados en una tarjeta. Vista día: rejilla por franjas con citas por estado y franjas «Disponible» con borde punteado turquesa (solo las que calcula el controlador). Vista semana: 1 columna en móvil, 3 en tablet, 6 desde 1400 px; el día actual lleva borde turquesa e insignia «Hoy».
- **Citas del día (CU12):** lista de tarjetas (hora, estado, paciente, odontólogo, motivo) con el cambio de estado en un panel propio; sin tabla, una sola maquetación para todos los anchos.
- **Programar cita (CU10):** dos pasos numerados; campos agrupados (Paciente y profesional / Fecha y horario / Motivo); hora de fin calculada destacada; las franjas libres sugeridas (flujo A2) son botones dentro de la alerta de error.
- **Ficha de cita:** resumen con bloque de fecha, datos en `dl`, panel «Actualizar estado»; modales de reprogramar y cancelar con icono, errores y franjas libres. `<x-modal>` ahora usa velo `brand-900` y `rounded-2xl`.

## Consolidación visual y responsive (base para el ciclo 3)
Componentes y estilos compartidos; usarlos en todas las pantallas nuevas en lugar de repetir clases.
- **`<x-pagina>`**: contenedor del contenido (`max-w-7xl`, margen lateral `px-4 sm:px-6 lg:px-8`, separación vertical `space-y-6`). `ancho="4xl"` lo estrecha para formularios.
- **`<x-cabecera-pagina titulo subtitulo :volver volver-texto>`**: para el slot `header`: enlace de regreso, título y descripción.
- **`<x-insignia tono="marca|exito|error|aviso|info|neutro">`**: insignia de estado con punto y texto. `x-estado-cita` y `x-insignia-activo` la usan; para estados nuevos (plan, tratamiento aplicado, pago vigente/anulado) usar el tono que corresponda (en proceso = marca, concluido = exito, cancelado/anulado = error, planificado/pendiente = aviso o neutro).
- **Listados**: clases `.dr-table` (tabla con cabecera `surface`, filas separadas y hover; las celdas primera y última llevan `px-5`) y `.dr-item` (tarjeta de la versión móvil). Patrón: `<div class="hidden xl:block"><table class="dr-table">…` y `<ul class="grid … xl:hidden"><li class="dr-item">…`; el punto de corte (`md` o `xl`) depende de cuántas columnas tenga la tabla. Una tabla con scroll horizontal solo es aceptable como último recurso.
- **`<x-agenda.cita :cita>`**: cita de la agenda diaria, con borde y fondo según su estado.
- **Agenda diaria**: desde 768 px, rejilla por franjas con las citas simultáneas en columnas (las franjas «Disponible» compartidas muestran solo el «+» y el texto «Programar» queda para lectores de pantalla). Por debajo de 768 px es una **lista vertical**: cada hora apila sus citas (programadas primero, canceladas después) y, si está libre, su franja «Disponible» a ancho completo (alto táctil mínimo 44 px). Ambas vistas salen de los mismos datos del controlador: la disponibilidad no cambia.
- **Anchos verificados**: 320, 375, 390, 430, 768, 1024, 1280 y 1440 px, sin desbordamiento horizontal en Login, Panel, Pacientes, Citas y Agenda.

## Atención Clínica del ciclo 3 (implementado)
Pantallas: `historias/show`, `planes/index`, `planes/show`, `tratamientos-aplicados/create` y `tratamientos-aplicados/show`.
- **Componentes nuevos de apoyo**: `<x-estado-clinico :estado>` (plan y tratamiento aplicado: activo y en proceso = turquesa, planificado = azul, concluido = verde, cancelado = rojo), `<x-moneda :monto>` (Bs 1.900,00; sin saltos de línea, para todos los importes) y `<x-dato etiqueta>` (dato de una ficha dentro de un `<dl>`).
- **Ficha con resumen**: tarjeta superior con lo esencial (nombre, estado, importe, avance) y debajo los datos en `<dl>` sin repetir lo ya mostrado en el resumen.
- **Listados**: planes y tratamientos del plan usan tabla `dr-table` desde 1280 px y tarjetas `dr-item` por debajo (1 columna, 2 desde 768 px). Cuando cada fila lleva formularios propios (antecedentes de la historia) se usa una única lista de filas en todos los anchos, para no duplicar formularios. El avance de un tratamiento es «Sesión X de Y» con una barra fina.
- **Formularios**: `<x-campo>` con `*` en los obligatorios y ayuda bajo el campo, agrupados en `<fieldset>` con leyenda en mayúsculas, errores junto al campo y botones apilados en móvil (acción principal arriba). Los importes llevan prefijo «Bs». Los avisos de negocio usan `<x-alerta tipo="aviso">` (p. ej. estudios previos faltantes, sesiones pendientes); las acciones destructivas llevan borde y título rojos y conservan `confirm()`.
- **Estados vacíos**: icono, mensaje y, si aplica, la acción para empezar.

## Pagos y Estado de cuenta (implementado)
Pantallas: `pagos/cuenta` y `pagos/create`.
- **Importes**: siempre con `<x-moneda :monto>` (Bs 1.250,00, sin saltos de línea); las cifras las calcula el backend y la vista solo las muestra. Una anulación se distingue por la insignia roja «Anulado», el monto tachado y en gris (`text-ink-mute line-through`) y el motivo visible; un abono vigente usa la insignia verde «Vigente».
- **Códigos de color financieros**: costo acordado = neutro (`surface`), total abonado = verde, saldo pendiente = ámbar mientras sea mayor que 0; con saldo 0 aparece la insignia «Saldado». Los totales del paciente van arriba, junto a sus datos.
- **Estado de cuenta**: una tarjeta por tratamiento (nombre, estado, enlace al plan, acción «Registrar abono», tres cifras y los abonos). Los abonos usan tabla `dr-table` desde 1024 px y tarjetas `dr-item` por debajo; la anulación (`<x-pagos.anular :pago>`, motivo + botón rojo suave + `confirm()`) es el mismo componente en ambas, para no duplicar el formulario en el código.
- **Registrar abono**: resumen del tratamiento con las tres cifras y el formulario en dos grupos (Importe y fecha / Método y observaciones). El monto lleva prefijo «Bs» y muestra el saldo pendiente como ayuda; los errores del backend (monto mayor al saldo, fecha posterior a hoy) salen junto al campo.

## Reportes (implementado)
Pantallas: `reportes/clinicos`, `agenda`, `financieros` y los parciales `pestanas`, `estatico`, `periodo-columnas` y `resultado`. La plantilla `reportes/pdf.blade.php` es de exportación y no forma parte del frontend.
- **Navegación**: los tres tipos de reporte son botones segmentados (`btn-primary` el actual, `btn-secondary` los demás) que pasan a varias líneas en móvil; los errores de validación salen en una `x-alerta` sobre los formularios.
- **Formularios**: dos tarjetas con cabecera `surface` («Reportes predefinidos» y «Reporte personalizado»). El personalizado se agrupa en Filtros, Periodo y Columnas del reporte; las columnas son casillas grandes (`has-[:checked]`, 44 px de alto) y el botón Generar ocupa todo el ancho en móvil.
- **Resultado**: cabecera con título, periodo y botones «Exportar PDF» y «Exportar Excel» (mismos parámetros que el reporte en pantalla, icono `download`); totales del backend como tarjetas de cifras; después tabla `dr-table` y, por debajo, tarjetas `dr-item` (la primera columna es el título y las demás pares etiqueta/valor, para que no se pierda ninguna). Con más de 5 columnas la tabla aparece desde 1280 px; con 5 o menos, desde 1024 px. Sin registros: estado vacío que conserva los totales.
- **`<x-reportes.valor :resultado :clave :valor>`**: pinta cada celda o total: dinero con `<x-moneda>`, la columna `estado` con la insignia que corresponda (cita, plan/tratamiento o abono vigente/anulado) y el resto con el texto de `ReporteService::formatear`, el mismo de la pantalla y del PDF. Las columnas numéricas se alinean a la derecha con cifras tabulares.
- **Gráficos**: no se agregan. El backend entrega tablas con columnas elegibles y totales, sin series estables que justifiquen una librería de gráficos.

## Pendiente
- Rediseñar con estos componentes el resto del ciclo 3, en este orden: Usuarios, Bitácora y Cambiar contraseña; Especialidades, Tratamientos y demás catálogos.
- Sustituir las clases `gray-*`/`indigo-*` sueltas de esos módulos por los tokens.
- Favicon derivado del isotipo; versión transparente del logo (opcional).
