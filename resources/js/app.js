import './bootstrap';
import '@fontsource/vazirmatn/400.css';
import '@fontsource/vazirmatn/500.css';
import '@fontsource/vazirmatn/600.css';
import '@fontsource/vazirmatn/700.css';
import '@fontsource/vazirmatn/800.css';
import '@fontsource/vazirmatn/900.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

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

    const template = firstRow.cloneNode(true);

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
        const row = template.cloneNode(true);
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
