// Run: node tests/js/location-hours-editor.test.cjs
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../assets/js/admin/location-editor.js'), 'utf8');
const start = source.indexOf('        function initHoursEditor() {');
const end = source.indexOf('        function clearFieldError', start);
assert.ok(start >= 0 && end > start);
const initSource = source.slice(start, end);
function fixture(saved) {
    let dirty = 0, parentEvents = 0;
    const output = { value: saved }, message = {}, summary = {};
    const classes = () => ({ toggle() {}, remove() {} });
    const button = () => ({ handlers: {}, addEventListener(type, fn) { this.handlers[type] = fn; }, click() { this.handlers.click(); } });
    const rows = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'].map(name => {
        const row = { dataset: { hoursLabel: name, hoursDay: name.slice(0,3) }, classList: classes() };
        const label = { hidden: true };
        const controls = {};
        ['closed','open','close','copy-target'].forEach(key => {
            controls['[data-hours-' + key + ']'] = { checked: key === 'closed', value: '', disabled: false,
                closest(selector) { return selector === 'label' ? label : row; },
                matches(selector) { return selector.split(', ').includes('[data-hours-' + key + ']'); }
            };
        });
        controls['[data-hours-copy-source]'] = button();
        row.querySelector = selector => controls[selector];
        row.querySelectorAll = () => [controls['[data-hours-open]'], controls['[data-hours-close]']];
        return row;
    });
    const apply = button(), cancel = button();
    const toolbar = { hidden: true, querySelector: s => s === '[data-hours-copy-apply]' ? apply : cancel };
    const editor = { handlers: {}, querySelectorAll: () => rows,
        querySelector: s => ({ '[data-hours-output]': output, '[data-hours-message]': message,
            '[data-hours-copy-toolbar]': toolbar, '[data-hours-copy-summary]': summary })[s],
        addEventListener(type, fn) { this.handlers[type] = fn; }
    };
    const setup = new Function('form', 'markDirty', 't', 'var hoursValid = true;\n' + initSource + '\ninitHoursEditor(); return () => hoursValid;');
    const isValid = setup({ querySelector: () => editor }, () => dirty++, (key, fallback) => fallback);
    return { rows, output, message, toolbar, apply, cancel, isValid,
        dirty: () => dirty, parentEvents: () => parentEvents,
        control(i, key) { return rows[i].querySelector('[data-hours-' + key + ']'); },
        copy(i) { rows[i].querySelector('[data-hours-copy-source]').click(); },
        event(i, key, type = 'change') {
            let stopped = false;
            editor.handlers[type]({ target: this.control(i, key), stopPropagation() { stopped = true; } });
            if (!stopped) { parentEvents++; }
        }
    };
}
const saved = ['Monday: 10:00–20:00','Tuesday: 11:00–18:00','Wednesday: 11:00–18:00',
    'Thursday: 11:00–18:00','Friday: 11:00–18:00','Saturday: 11:00–16:00','Sunday: Closed'].join('\n');
const f = fixture(saved);
assert.equal(f.control(0,'open').value, '10:00');
assert.equal(f.control(5,'close').value, '16:00');
assert.equal(f.control(6,'closed').checked, true);
assert.equal(f.output.value, saved);
assert.equal(f.dirty(), 0);
f.copy(0);
f.control(1,'copy-target').checked = true;
f.event(1,'copy-target','input');
f.event(1,'copy-target');
f.cancel.click();
assert.equal(f.output.value, saved);
assert.equal(f.dirty(), 0);
assert.equal(f.parentEvents(), 0);
assert.equal(f.toolbar.hidden, true);
f.copy(0);
for (let i=1;i<=5;i++) { f.control(i,'copy-target').checked=true; f.event(i,'copy-target'); }
f.apply.click();
assert.equal(f.dirty(), 1);
assert.equal(f.control(5,'close').value, '20:00');
assert.equal(f.control(6,'closed').checked, true);
assert.ok(f.isValid());
const reopened = fixture(f.output.value); // Persistence boundary: reload exact submitted text.
assert.equal(reopened.control(5,'open').value, '10:00');
assert.equal(reopened.control(5,'close').value, '20:00');
assert.equal(reopened.dirty(), 0);
reopened.copy(0); reopened.apply.click(); assert.equal(reopened.dirty(), 0);
reopened.control(0,'open').value='';
reopened.event(0,'open');
assert.equal(reopened.isValid(), false);
const beforeInvalid = reopened.output.value;
reopened.copy(0); reopened.control(1,'copy-target').checked=true; reopened.apply.click();
assert.equal(reopened.control(1,'open').value, '10:00');
assert.equal(reopened.output.value, beforeInvalid);
const legacy = fixture('Mon-Sat by appointment');
assert.equal(legacy.output.value, 'Mon-Sat by appointment');
assert.equal(legacy.dirty(), 0);
legacy.copy(0); legacy.control(1,'copy-target').checked=true; legacy.event(1,'copy-target'); legacy.cancel.click();
assert.equal(legacy.output.value, 'Mon-Sat by appointment');
assert.equal(legacy.dirty(), 0);
const closed = fixture(saved);
closed.copy(6); closed.control(5,'copy-target').checked=true; closed.apply.click();
assert.equal(closed.control(5,'closed').checked,true);
assert.equal(fixture(closed.output.value).control(5,'closed').checked,true);
const switcher=fixture(saved);
switcher.copy(0); switcher.control(1,'copy-target').checked=true; switcher.copy(2); switcher.apply.click();
assert.equal(switcher.output.value,saved);
assert.equal(switcher.dirty(),0);
const crlf=fixture(saved.replace(/\n/g,'\r\n'));
assert.equal(crlf.control(0,'open').value,'10:00');
assert.equal(crlf.output.value,saved.replace(/\n/g,'\r\n'));
console.log('PASS: hydration, cancel, selection isolation, selected copy, closed copy, invalid input, reload, legacy preservation, source reset, CRLF.');

const aliasStart = source.indexOf('        function appendSubmissionAliases(data) {');
const aliasEnd = source.indexOf('        function setSaveState', aliasStart);
assert.ok(aliasStart >= 0 && aliasEnd > aliasStart);
const aliases = new Function('field','value', source.slice(aliasStart,aliasEnd) + '\nreturn appendSubmissionAliases;')(
    name => name === 'hours' || name === 'address', name => name === 'hours' ? saved : '');
const payload = new Map();
aliases(payload);
assert.equal(payload.get('hours'), saved);
assert.equal(payload.get('address'), '');
assert.equal(payload.has('phone'), false);
assert.equal(payload.has('email'), false);
assert.equal(payload.has('website'), false);
console.log('PASS: absent contact fields are omitted, explicit empty fields retained.');
