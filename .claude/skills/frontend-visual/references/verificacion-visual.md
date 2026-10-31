# Verificación visual

Objetivo: comprobar que la interfaz se ve y funciona como se diseñó, y ser honesto sobre lo que no se pudo comprobar.

## 1. Levantar la app

1. Busca cómo se arranca en `package.json` (`scripts`), `Makefile`, `composer.json`, `README` o similar, y usa ese comando. Ejecútalo en segundo plano.
2. Antes de arrancar, comprueba que el entorno apunta a datos locales o de prueba (sin editar `.env`). Si apunta a producción o a una base real, no arranques y avisa.
3. Si no hay servidor (HTML estático), abre el archivo directamente o sirve la carpeta con el servidor estático que ya haya en el proyecto.

## 2. Herramienta de captura (por orden)

1. **Navegador disponible en la sesión** (extensión Claude in Chrome o un MCP de navegador): úsalo.
2. **Playwright ya instalado en el proyecto:** úsalo (ejemplo abajo).
3. **Ninguna de las dos:** pregunta antes de descargar Playwright y sus navegadores (son cientos de MB). Si el usuario no quiere, pasa al punto 4 de esta sección.
4. **Sin forma de ver la página:** no finjas. Haz solo la verificación técnica (build, lint) y escribe en el resumen: "No verificado visualmente: <motivo>", más una lista corta de qué revisar a mano (anchos, estados, foco).

## 3. Qué capturar y revisar

- **Anchos:** 390, 768, 1024 y 1440 px.
- **Estados:** normal, vacío, error, carga y contenido extremo (título largo, muchos registros).
- **Teclado:** recorre con Tab y confirma que el foco siempre se ve y el orden es lógico.
- **Overflow horizontal:** debe ser nulo. Prueba rápida en consola:
  `document.documentElement.scrollWidth > window.innerWidth`
- **Texto:** sin cortes críticos, sin líneas demasiado largas, contraste legible.
- **Ritmo:** alineaciones, espaciado vertical y jerarquía coherentes con `DESIGN.md`.

Corrige lo que encuentres y vuelve a capturar solo lo afectado.

## 4. Ejemplo con Playwright (si ya está instalado)

Guarda el script y las capturas fuera del repo (por ejemplo en `/tmp`), o bórralos al terminar, para no ensuciar `git status`.

```js
// node shot.mjs http://localhost:3000/ruta
import { chromium } from 'playwright';
const url = process.argv[2];
const sizes = [[390, 844], [768, 1024], [1024, 768], [1440, 900]];
const browser = await chromium.launch();
for (const [w, h] of sizes) {
  const page = await browser.newPage({ viewport: { width: w, height: h } });
  await page.goto(url, { waitUntil: 'networkidle' });
  const overflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  console.log(`${w}px:`, overflow ? 'SCROLL HORIZONTAL' : 'ok');
  await page.screenshot({ path: `/tmp/shot-${w}.png`, fullPage: true });
  await page.close();
}
await browser.close();
```
