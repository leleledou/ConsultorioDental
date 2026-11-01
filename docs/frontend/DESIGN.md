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

## Administración: Usuarios, Bitácora y Cambiar contraseña (implementado)
Pantallas: `usuarios/index`, `usuarios/show`, `bitacora/index`, `bitacora/show` y `contrasena/cambiar` (solo administrador, salvo Cambiar contraseña, que usan los dos roles).
- **Usuarios**: mismo patrón que Pacientes (buscador + «Nuevo usuario», contador, tabla `dr-table` desde 1280 px y tarjetas `dr-item` por debajo, formulario en panel con `x-data`). El formulario se agrupa en Datos personales, Datos profesionales, Contacto y Acceso al sistema (rol y contraseña inicial solo al registrar, dentro de `<template x-if>`). Rol: `<x-insignia>` turquesa para administrador y azul para odontólogo; estado con `x-insignia-activo`; bloqueo con insignia roja. Las cinco acciones de una fila viven en `<x-usuarios.acciones :u>` (tabla y tarjeta comparten el mismo código); la contraseña temporal se muestra en una `x-alerta tipo="aviso"` con borde ámbar y texto monoespaciado seleccionable.
- **Bitácora**: filtros en tarjeta (`x-campo`), contador, tabla desde 1280 px y tarjetas por debajo con fecha, hora, usuario, acción, tabla, registro e IP completos. `<x-bitacora.accion :accion>` muestra el texto exacto de la acción en monoespaciado; el color solo depende del verbo con que empieza (rojo: fallos de acceso, bajas, anulaciones y cancelaciones; verde: altas, registros y habilitaciones; turquesa: modificaciones, cambios y restablecimientos; gris: el resto, p. ej. cierres de sesión). No se agregan categorías ni textos que no existan en el backend.
- **Cambiar contraseña**: tarjeta con cabecera e icono de llave, tres campos con `x-campo` (contraseña actual, nueva con la política como ayuda y confirmación), errores junto a cada campo y el aviso de contraseña temporal como `x-alerta tipo="aviso"`.
- **Fichas** (`usuarios/show`, `bitacora/show`): resumen con avatar o acción y datos con `x-dato`; el acceso del usuario (estado, intentos, bloqueo, cambio obligatorio) en una tarjeta lateral.

## Catálogos, etapa A: Especialidades, Tratamientos, Estudios requeridos y Tarifas (implementado)
Pantallas: `especialidades/index` y `show`, `tratamientos/index`, `show` y `estudios`, `tarifas/index` y `show` (solo administrador). Es el patrón que seguirán el resto de los catálogos.
- **Listado de catálogo**: tarjeta con buscador y «Nuevo …» (o solo contador si no hay buscador), tabla `dr-table` y tarjetas `dr-item`, panel de formulario con `x-data` (`nuevo()` / `editar()`), y las acciones de fila en un componente `x-<módulo>.acciones` compartido por la tabla y la tarjeta (`x-especialidades.acciones`, `x-tratamientos.acciones`, `x-tratamientos.requerido-acciones`). Punto de corte de la tabla: `md` si tiene 4 columnas o menos, `lg` o `xl` si las acciones son muchas.
- **Estados de catálogo**: «Disponible»/«No disponible» (tratamientos) y «Vigente»/«Cerrada» (tarifas) con `x-insignia` verde/gris; «Obligatorio» turquesa y «Recomendado» gris; «Sin tarifa» ámbar. Para activo/inactivo se usa `x-insignia-activo`.
- **Casillas de formulario**: una sola fila táctil (`min-h-11`, borde que pasa a turquesa al marcarla con `has-[:checked]`) en lugar de la casilla suelta; el campo oculto con 0 se conserva.
- **Importes de tarifas**: siempre con `x-moneda`; el campo de precio lleva prefijo «Bs» y `inputmode="decimal"`. En la ficha del tratamiento, un resumen con el precio vigente (la tarifa con estado vigente que ya entrega el backend) y el historial debajo.
- **Fichas con relaciones**: la especialidad lista sus usuarios con enlace y estado; el tratamiento muestra sus datos con `x-dato` y conserva «Dar de baja»/«Habilitar» al pie.

## Catálogos, etapa B: Diagnósticos, Tipos de estudio, Materiales y Antecedentes médicos (implementado)
Pantallas: `diagnosticos`, `tipos-estudio`, `materiales` y `antecedentes` (`index` y `show` de cada uno, solo administrador). Siguen el patrón de la etapa A.
- **Catálogos con estado activo/inactivo** (diagnósticos, tipos de estudio, materiales): `x-insignia-activo` en tabla, tarjeta y ficha; acciones de fila en `x-catalogos.acciones` (Modificar + Dar de baja/Habilitar) y, en la ficha, `x-catalogos.baja-habilitar` a ancho completo en móvil. Ambos reciben el prefijo de rutas (`ruta="materiales"`), la etiqueta del confirm y, el primero, los campos que carga `editar()`.
- **Antecedentes** no tienen estado (el backend borra de verdad): sin insignia, columna «N.º de historias» y `x-antecedentes.acciones` (Modificar + Eliminar). La ficha solo muestra nombre y descripción.
- **Puntos de corte**: tabla desde `lg` (diagnósticos, tipos, materiales) o `md` (antecedentes); por debajo, tarjetas en 1 columna (2 desde `md` cuando hay tabla desde `lg`).
- **Textos largos**: las descripciones no se recortan (`whitespace-pre-line break-words`, `max-w-md` en la tabla). El código del diagnóstico va en turquesa como enlace a la ficha; la tabla de materiales muestra categoría y unidad de medida, sin campos de inventario.
- **Formularios**: sin selectores nuevos; código (10) + nombre (60) en una fila para diagnósticos y categoría (20) + unidad (15) para materiales; errores por campo con `x-campo`.

## Catálogos, etapa C: Piezas dentales, Estados de pieza y Métodos de pago (implementado)
Pantallas: `piezas-dentales`, `estados-pieza` y `metodos-pago` (`index` y `show`, solo administrador). Con esta etapa todos los catálogos usan el patrón de la etapa A.
- **Piezas dentales** es un catálogo fijo de solo lectura (sin buscador, paginación ni acciones): contador, tabla desde `md` y tarjetas compactas (código FDI grande en turquesa, cuadrante, nombre, tipo · dentición). Sin insignias: no tienen estado.
- **Pestañas «Piezas y estados»**: `x-catalogos.pestanas` (`activa="estados"|"piezas"`), subrayado turquesa y objetivo táctil de 44 px; sustituye al `x-nav-link` de Breeze, que ya no se usa en ninguna vista.
- **Estados de pieza**: tabla desde `xl` (6 columnas), tarjetas por debajo. El color de referencia (#RRGGBB) se muestra con `x-estados-pieza.color` (muestra circular + código; solo pinta si cumple el formato) y el formulario previsualiza el color con Alpine sin añadir campos.
- **Métodos de pago**: nombre y descripción, mismo patrón que tipos de estudio (tabla desde `lg`). Sin comisiones ni datos de pasarela: el backend solo guarda nombre y descripción.
- Las acciones de baja/habilitar reutilizan `x-catalogos.acciones` y `x-catalogos.baja-habilitar` (`ruta="estados-pieza"`, `ruta="metodos-pago"`).

## Correcciones finales de la auditoría (responsive, accesibilidad y errores) (implementado)
- **Puntos de corte de tablas**: las tablas con acciones en su última columna (`Estado de cuenta → Abonos` y `Estudios requeridos`) pasan de `lg` a `xl` (1280 px). A 1024 px el menú lateral fijo deja unos 650 px útiles y la tarjeta (`overflow-hidden`) recortaba la columna; por debajo de 1280 px se usan tarjetas con todas las acciones. Regla general: una tabla con acciones solo empieza en `lg` si cabe en ~650 px; si no, en `xl`. Comprobar siempre los recortes por `overflow-hidden`, no solo `scrollWidth`.
- **Título de página**: `x-cabecera-pagina` (y las cabeceras de citas y pacientes escritas a mano) usa `<h1>`; `.page-header h1` conserva el aspecto anterior. Los demás títulos siguen siendo `h2`/`h3`.
- **Idioma**: los layouts `app` y `guest` fijan `lang="es"`. El locale de Laravel (`APP_LOCALE=en` en `.env`) no se modificó: pendiente de decisión si se quieren mensajes de validación, fechas y paginación por defecto en español.
- **Táctil**: `.btn-sm` mide 44 px de alto por debajo de 640 px (`max-sm:min-h-11`); lo mismo los enlaces del menú en móvil, los selectores Día/Semana, las franjas sugeridas, el enlace «volver» de la cabecera y el botón de la hamburguesa (44 × 44 px). En escritorio no cambian.
- **Contraste**: `ink-mute` pasa de `#7B8794` a `#64707D` (≥ 4,5 : 1 sobre blanco, `surface` y `brand-50`) y se usa en guiones de valores vacíos y marcadores.
- **Texto pequeño**: el código de paquete (P1…P5) y la etiqueta «Próximo ciclo» del menú pasan de 11/10 px a 12 px (`text-xs`).
- **Sin recortes de datos**: CI/teléfono y nombre del paciente (listado y ficha), citas de la agenda (día y semana) y el contexto de la cabecera superior se ajustan con `break-words`/varias líneas en lugar de `truncate`. Se conserva `truncate` solo en el menú de usuario (nombre completo en el desplegable) y en etiquetas fijas.
- **ids únicos**: `x-usuarios.acciones` recibe `sufijo` (`-tabla`/`-tarjeta`) porque se dibuja en la tabla y en la tarjeta.
- **Páginas de error**: `resources/views/errors/{403,404,419,500,minimal}.blade.php` usan `x-error-pagina` (documento propio: logo, código, título `h1`, mensaje fijo y «Ir al panel»/«Ir al inicio de sesión» + «Volver»). No muestran el mensaje de la excepción ni datos internos y conservan el código HTTP; `minimal` cubre 401, 429, 503, etc. Con `APP_DEBUG=true` Laravel sigue mostrando su página de depuración.

## Pendiente
- Pruebas funcionales finales con datos ficticios (solo en la base auxiliar), decisión sobre `APP_LOCALE` y limpieza de los componentes Breeze sobrantes.
- Componentes sobrantes (sin eliminar): `components/nav-link`, `components/responsive-nav-link` y `welcome.blade.php` (Breeze) ya no se usan.
- Favicon derivado del isotipo; versión transparente del logo (opcional).
