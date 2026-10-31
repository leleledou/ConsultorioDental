# Checklist de entrega visual

Revisión final. Los criterios detallados están en `SKILL.md`; aquí solo se comprueba que se cumplieron.

## Identidad y estética
- [ ] La dirección visual encaja con el producto y no se siente derivada de una plantilla
- [ ] Hay jerarquía reconocible de títulos, acciones y contenido, y el foco se ve en tres segundos
- [ ] Tipografía, color, retícula y espaciado salen de los tokens y son consistentes
- [ ] Componentes y estados con apariencia unificada
- [ ] Imágenes e iconos relevantes y coherentes
- [ ] `DESIGN.md` o los tokens del repo se respetaron o se actualizaron

## Interacción y contenido
- [ ] Navegación, botones y enlaces funcionan
- [ ] Los formularios conservan nombres de campo, valores, validaciones, errores y acciones
- [ ] Estados de carga, vacío, error y éxito visibles donde corresponde
- [ ] No hay datos de demostración presentados como reales
- [ ] No se perdió contenido ni funciones respecto a la versión previa

## Responsive y accesibilidad
- [ ] Revisado en 390, 768, 1024 y 1440 px, o declarado como no verificado
- [ ] Sin scroll horizontal accidental ni controles fuera de pantalla
- [ ] Foco visible y navegación por teclado
- [ ] Contraste suficiente; el color no es la única señal de estado
- [ ] `prefers-reduced-motion` respetado
- [ ] Imágenes con espacio reservado (sin saltos de layout)

## Calidad técnica
- [ ] Respeta framework y convenciones del repositorio
- [ ] Sin dependencias, fuentes ni servicios externos no autorizados
- [ ] Sin cambios en datos, backend, `.env` ni migraciones
- [ ] Build, lint y tests relevantes ejecutados cuando fue posible
- [ ] El resumen indica lo verificado, lo no verificado y por qué
- [ ] No se ejecutó `git add`, `commit`, `push` ni `merge`
