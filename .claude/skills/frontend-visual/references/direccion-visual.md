# Referencia: dirección visual y composición

Esta guía es inspiración y método, no un catálogo para copiar ni una paleta predeterminada.

## Lenguajes visuales posibles

| Dirección | Tipografía | Composición | Elementos característicos |
|---|---|---|---|
| Editorial / premium | Serif expresiva + sans legible | Retícula asimétrica, titulares de impacto | Espacios amplios, fotografía con intención, líneas finas |
| Tecnología precisa | Sans geométrica/humanista | Retícula modular y datos claros | Contrastes definidos, detalles mínimos, acento calibrado |
| Cálido y artesanal | Tipografía con personalidad | Secciones orgánicas | Tonos terrosos, texturas suaves, imágenes auténticas |
| Producto SaaS | Sans muy legible | Navegación consistente y acciones directas | Sistema de componentes, estados claros, buena densidad |
| Institucional confiable | Sans neutra y títulos firmes | Orden y jerarquía rigurosos | Contraste sobrio, facilidad de uso, fotografía genuina |
| Creativo experimental | Tipografía destacada | Contraste, secciones con ritmo variable | Asimetría controlada y animación con moderación |

No elijas siempre el primer estilo. Decide según el dominio del proyecto, la audiencia, el nivel de formalidad y las referencias aportadas.

## Cómo lograr una interfaz que no parezca una plantilla

1. Elige **una** idea dominante de composición: retícula editorial, navegación lateral limpia, escaparate visual, página narrativa, tablero de densidad media, etc.
2. Crea contraste de escala deliberado: un título o dato dominante y elementos secundarios claramente subordinados.
3. Define un patrón de alineación y repítelo. Una retícula coherente se nota más que diez efectos decorativos.
4. Deja que la tipografía, el espacio, las imágenes y el contenido real conduzcan la mirada.
5. Varía el ritmo entre secciones (agrupaciones, tamaños, divisores, fondos, densidad) sin romper la identidad.
6. Usa el acento cromático para acciones y focos, no para decorar cada elemento.
7. Añade microdetalles con sentido: hover, focus, selección, carga, feedback de acción, transiciones breves.
8. Audita los casos largos: títulos extensos, traducciones, vacío, contenido inesperado, muchos datos.

## Ejemplo trabajado: de encargo a decisiones

Es un ejemplo de razonamiento, no una plantilla de estilo. Otro encargo llevaría a otras decisiones.

**Encargo:** "Rediseña el panel de inventario de una ferretería de barrio. Lo usan el dueño y dos empleados, casi siempre desde un computador de mostrador."

**Lectura:** herramienta interna, uso diario y rápido, usuarios no técnicos, mucha tabla. Prima escanear y buscar, no impresionar.

**Concepto:** "taller ordenado" (funcional, claro, con calidez).

**Tokens (resumen):**

```css
:root {
  --bg: #f6f4ef;          /* papel cálido, no blanco puro */
  --surface: #ffffff;
  --text: #1d1f23;
  --text-muted: #5b606a;  /* contraste >= 4.5:1 sobre --surface */
  --border: #dcd8cf;
  --accent: #c2410c;      /* solo acciones primarias y foco */
  --danger: #b42318;      /* siempre acompañado de icono o texto */
  --radius: 6px;          /* una sola regla de radio */
  --space: 4px;           /* base; todo múltiplo de 4 */
}
```

**Tipografía:** sans humanista del sistema para texto (14-15 px en tablas), peso 600 para encabezados de tabla, un serif solo en el título de pantalla para dar identidad sin cargar fuentes externas.

**Layout:**
- Navegación lateral compacta a la izquierda; contenido a ancho completo.
- Arriba, tres indicadores (stock bajo, agotados, valor total) como números grandes sin tarjeta, separados por líneas finas; es el punto focal.
- Debajo, buscador y filtros siempre visibles y una tabla densa con alineación numérica a la derecha. No se convierte cada fila en tarjeta.
- Acciones por fila: "Editar" discreta; "Eliminar" separada, en rojo y con confirmación.

**Estados:** tabla vacía con mensaje y acción de "Agregar producto"; stock bajo con icono además de color; carga con filas esqueleto.

**Móvil:** la tabla pasa a lista de filas apiladas con producto, stock y acción visibles; los filtros se agrupan bajo un botón.

**Qué se descartó y por qué:** gradientes y glassmorphism (restan legibilidad en una herramienta de uso diario), tarjetas por fila (menos densidad), modo oscuro (no se pidió y el mostrador es luminoso).

## Por clase de interfaz

**Landing / marketing:** propuesta de valor clara, hero con identidad, secuencia visual, credibilidad sin testimonios inventados, CTA distinguible y cierre convincente. Evita el patrón repetitivo "hero + tres tarjetas + FAQ" si no sirve al contenido.

**Dashboard / aplicaciones:** escaneabilidad primero. Jerarquía entre indicadores, herramientas y datos. Densidad apropiada; tablas con alineación consistente, filtros visibles, paginación y acciones diferenciadas.

**Comercio electrónico:** fotografías auténticas, tarjetas de producto claras, variantes, precio, disponibilidad, confianza y compra sin obstáculos. No inventes reseñas ni descuentos.

**Portafolio:** tipografía expresiva, proyectos destacados, buen uso de imágenes reales, transiciones cuidadas y narrativa ordenada.

**Formularios:** etiquetas persistentes, agrupaciones lógicas, validación contextual, espacios consistentes y progreso visible solo cuando existe un proceso por pasos real.

**Herramientas internas:** prioridad a rapidez, legibilidad, búsqueda y acciones frecuentes. Evita efectos excesivos y conserva los controles técnicos importantes.

## Puntos de control visual

- ¿Se reconoce el foco de la pantalla en tres segundos?
- ¿La composición tiene ritmo propio o se siente derivada de una plantilla?
- ¿Hay diferencia clara entre acciones primarias, secundarias y destructivas?
- ¿Bordes, fondos, sombras, tamaños y estados obedecen a un mismo sistema?
- ¿Funciona la estructura sin imágenes, con poco contenido o con demasiados registros?
- ¿La experiencia móvil se diseñó o solo se apiló?
- ¿El diseño coincide con el sector, el tono y las expectativas del usuario?
