# resources/js/shared/

Shared JS utilities dùng cho mọi module NWIDART.

| File | Dùng khi |
|---|---|
| `form-controller.js` | Mọi Alpine form cần validation client-side |
| `wizard-controller.js` | Form multi-step wizard |
| `tom-select-factory.js` | Trang có TomSelect enhanced select |

## Import trong module

Module `vite.config.js` cần khai báo alias `@shared`:

```js
resolve: {
    alias: {
        '@shared': path.resolve(__dirname, '../../resources/js/shared'),
    }
}
```

Sau đó:

```js
import { makeFormController }   from '@shared/form-controller.js';
import { makeWizardController } from '@shared/wizard-controller.js';
import { createTs, createTsRemote } from '@shared/tom-select-factory.js';
```

## Không cần import nếu dùng global

`initFormValidation`, `initTomSelect`, `initOrgAddress`, `window.TomSelect`,
`window.Alpine`, `window.$` — đã expose qua `resources/js/app.js` core bundle.
Chỉ import từ `@shared` khi cần cấu trúc Alpine component có tổ chức hơn.
