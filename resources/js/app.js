import './bootstrap';
import '@fontsource/vazirmatn/400.css';
import '@fontsource/vazirmatn/500.css';
import '@fontsource/vazirmatn/600.css';
import '@fontsource/vazirmatn/700.css';
import '@fontsource/vazirmatn/800.css';
import '@fontsource/vazirmatn/900.css';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
window.jalaliDatepicker?.startWatch({
    separatorChars: { date: '/', between: ' ', time: ':', targetDate: '-', targetBetween: ' ', targetTime: ':' },
});

// Localize visible numbers without changing form values, IDs or data sent to Laravel.
const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
const localizeVisibleNumber = (value) => value
    .replace(/\d/g, (digit) => persianDigits[Number(digit)])
    .replace(/(?<=[۰-۹]),(?=[۰-۹])/g, '٬');

const localizeTextNode = (node) => {
    if (!node.nodeValue || node.parentElement?.closest('script, style, code, pre')) return;
    const localized = localizeVisibleNumber(node.nodeValue);
    if (localized !== node.nodeValue) node.nodeValue = localized;
};

const localizeTree = (root) => {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let node;
    while ((node = walker.nextNode())) localizeTextNode(node);
};

localizeTree(document.body);
new MutationObserver((mutations) => mutations.forEach((mutation) => {
    if (mutation.type === 'characterData') localizeTextNode(mutation.target);
    mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.TEXT_NODE) localizeTextNode(node);
        if (node.nodeType === Node.ELEMENT_NODE) localizeTree(node);
    });
})).observe(document.body, { subtree: true, childList: true, characterData: true });

const asciiDigits = (value) => value.replace(/[۰-۹]/g, (digit) => String(persianDigits.indexOf(digit)));
const formatMoneyInput = (input) => {
    const raw = asciiDigits(input.value).replace(/[٬,\s]/g, '').replace(/[^\d.]/g, '');
    if (!raw) return;
    const [integer, decimal] = raw.split('.');
    input.value = localizeVisibleNumber(Number(integer || 0).toLocaleString('en-US') + (decimal !== undefined ? `.${decimal}` : ''));
};

document.addEventListener('input', (event) => {
    if (event.target.matches('[data-money-input]')) formatMoneyInput(event.target);
});
document.querySelectorAll('[data-money-input]').forEach(formatMoneyInput);

// Replace cramped native mobile select menus with an accessible bottom sheet.
const selectSheet = document.querySelector('[data-select-sheet]');
const selectSheetTitle = selectSheet?.querySelector('#select-sheet-title');
const selectSheetOptions = selectSheet?.querySelector('[data-select-sheet-options]');
let activeSelect = null;

const closeSelectSheet = () => {
    if (!selectSheet) return;
    selectSheet.hidden = true;
    document.body.classList.remove('overflow-hidden');
    activeSelect?.focus();
    activeSelect = null;
};

const openSelectSheet = (select) => {
    if (!selectSheet || !selectSheetTitle || !selectSheetOptions) return;
    activeSelect = select;
    selectSheetTitle.textContent = document.querySelector(`label[for="${CSS.escape(select.id)}"]`)?.textContent.replace('*', '').trim() || 'انتخاب گزینه';
    selectSheetOptions.replaceChildren();

    [...select.options].forEach((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `select-sheet-option${option.selected ? ' is-selected' : ''}`;
        button.disabled = option.disabled;
        const label = document.createElement('span');
        label.textContent = option.textContent;
        const check = document.createElement('span');
        check.className = 'select-sheet-check';
        button.append(label, check);
        button.addEventListener('click', () => {
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            closeSelectSheet();
        });
        selectSheetOptions.append(button);
    });

    selectSheet.hidden = false;
    document.body.classList.add('overflow-hidden');
    selectSheetOptions.querySelector('.is-selected')?.scrollIntoView({ block: 'center' });
};

document.querySelectorAll('select.input-text').forEach((select) => {
    select.addEventListener('mousedown', (event) => {
        if (window.matchMedia('(max-width: 1023px)').matches) {
            event.preventDefault();
            openSelectSheet(select);
        }
    });
});
selectSheet?.querySelectorAll('[data-select-sheet-close]').forEach((button) => button.addEventListener('click', closeSelectSheet));

document.querySelectorAll('[data-battery-control]').forEach((control) => {
    const slider = control.querySelector('.battery-slider');
    const value = control.querySelector('[data-battery-value]');
    const sync = () => {
        value.textContent = slider.value;
        slider.style.setProperty('--battery-progress', `${slider.value}%`);
    };
    slider.addEventListener('input', sync);
    sync();
});

const navigation = document.querySelector('#mobile-navigation');
const backdrop = document.querySelector('#navigation-backdrop');
const openButton = document.querySelector('[data-navigation-open]');
const closeButtons = document.querySelectorAll('[data-navigation-close]');

const setNavigationOpen = (isOpen) => {
    if (!navigation || !backdrop || !openButton) {
        return;
    }

    navigation.classList.toggle('hidden', !isOpen);
    backdrop.classList.toggle('hidden', !isOpen);
    openButton.setAttribute('aria-expanded', String(isOpen));
    document.body.classList.toggle('overflow-hidden', isOpen);

    if (isOpen) {
        navigation.querySelector('button, a')?.focus();
    } else {
        openButton.focus();
    }
};

openButton?.addEventListener('click', () => setNavigationOpen(true));
closeButtons.forEach((button) => button.addEventListener('click', () => setNavigationOpen(false)));

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && openButton?.getAttribute('aria-expanded') === 'true') {
        setNavigationOpen(false);
    }
});

// Repeatable purchase/sale invoice lines. Names are re-indexed after every
// operation so Laravel receives a clean `lines[n]` payload.
document.querySelectorAll('[data-repeatable-lines]').forEach((container) => {
    const addButton = container.querySelector('[data-add-line]');
    const firstRow = container.querySelector('[data-repeatable-row]');
    if (!addButton || !firstRow) return;

    const refresh = () => {
        const rows = [...container.querySelectorAll('[data-repeatable-row]')];
        rows.forEach((row, index) => {
            row.querySelector('[data-line-label]').textContent = `ردیف ${index + 1}`;
            const remove = row.querySelector('[data-remove-line]');
            remove?.classList.toggle('hidden', rows.length === 1);
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/lines\[\d+\]/, `lines[${index}]`);
            });
        });
    };

    const bindRemove = (row) => {
        row.querySelector('[data-remove-line]')?.addEventListener('click', () => {
            if (container.querySelectorAll('[data-repeatable-row]').length > 1) {
                row.remove();
                refresh();
            }
        });
    };

    bindRemove(firstRow);
    addButton.addEventListener('click', () => {
        const row = firstRow.cloneNode(true);
        row.querySelectorAll('input, textarea').forEach((field) => {
            if (field.type !== 'hidden') field.value = field.name.includes('[quantity]') ? '1' : ((field.name.includes('[discount_value]') || field.name.includes('[discount_toman]')) ? '0' : '');
        });
        row.querySelectorAll('select').forEach((field) => { field.selectedIndex = 0; });
        container.insertBefore(row, addButton);
        bindRemove(row);
        refresh();
        row.querySelector('select, input')?.focus();
    });
    refresh();
});

// Quick-create customer and product dialogs on the sales form.
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const partyDialog = document.querySelector('#quick-party-dialog');
const productDialog = document.querySelector('#quick-product-dialog');
let activeVariantSelect = null;

document.addEventListener('click', (event) => {
    const partyButton = event.target.closest('[data-open-quick-party]');
    if (partyButton && partyDialog) partyDialog.showModal();

    const productButton = event.target.closest('[data-open-quick-product]');
    if (productButton && productDialog) {
        activeVariantSelect = productButton.closest('[data-repeatable-row]')?.querySelector('[data-product-variant-select]') ?? null;
        productDialog.showModal();
    }

    if (event.target.closest('[data-close-dialog]')) event.target.closest('dialog')?.close();
});

const submitQuickCreate = async (container, button, onSuccess) => {
    const errors = container.querySelector('[data-form-errors]');
    const payload = Object.fromEntries([...container.querySelectorAll('[data-field]')].map((field) => [field.dataset.field, field.value]));
    button.disabled = true;
    errors.classList.add('hidden');

    try {
        const response = await fetch(container.dataset.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok) {
            const messages = data.errors ? Object.values(data.errors).flat() : [data.message || 'ثبت اطلاعات انجام نشد.'];
            errors.textContent = messages.join(' ');
            errors.classList.remove('hidden');
            return;
        }
        onSuccess(data);
        container.closest('dialog')?.close();
        container.querySelectorAll('input').forEach((input) => { input.value = ''; });
    } catch {
        errors.textContent = 'ارتباط با سرور برقرار نشد. دوباره تلاش کنید.';
        errors.classList.remove('hidden');
    } finally {
        button.disabled = false;
    }
};

const quickParty = document.querySelector('[data-quick-party]');
quickParty?.querySelector('[data-submit-quick-party]')?.addEventListener('click', (event) => {
    submitQuickCreate(quickParty, event.currentTarget, (party) => {
        const select = document.querySelector('#party_id');
        select.add(new Option(party.label, party.id, true, true));
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
});

const quickProduct = document.querySelector('[data-quick-product]');
quickProduct?.querySelector('[data-submit-quick-product]')?.addEventListener('click', (event) => {
    submitQuickCreate(quickProduct, event.currentTarget, (product) => {
        document.querySelectorAll('[data-product-variant-select]').forEach((select) => {
            select.add(new Option(`${product.label} - قیمت: ${Number(product.selling_price_toman).toLocaleString('fa-IR')}`, product.id, false, select === activeVariantSelect));
        });
        const row = activeVariantSelect?.closest('[data-repeatable-row]');
        const price = row?.querySelector('[name$="[unit_price_toman]"]');
        if (price) price.value = product.selling_price_toman;
        activeVariantSelect?.dispatchEvent(new Event('change', { bubbles: true }));
    });
});
