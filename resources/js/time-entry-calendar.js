const decimalFormat = new Intl.NumberFormat('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function formatDecimal(value) {
    return decimalFormat.format(value);
}

function parseHours(value) {
    const hours = parseFloat(String(value).replace(',', '.'));

    return Number.isNaN(hours) ? 0 : hours;
}

function firstErrorMessage(data) {
    if (data.errors) {
        return Object.values(data.errors)[0][0];
    }

    return data.message || 'Opslaan mislukt.';
}

/**
 * Month totals shared by every day cell of the calendar.
 */
export function timeEntryCalendar({ monthHours, monthAmount, weekHours, approvalStatus }) {
    return {
        monthHours,
        monthAmount,
        weekHours,
        approvalStatus,

        formatHours(hours) {
            return formatDecimal(hours);
        },

        formatWeekHours(week) {
            const hours = this.weekHours[week] ?? 0;

            return hours > 0 ? formatDecimal(hours) : '—';
        },

        formatAmount(amount) {
            return `€ ${formatDecimal(amount)}`;
        },
    };
}

/**
 * One editable calendar day; saves itself when focus leaves the cell.
 */
export function timeEntryDay({ url, week, hours, description }) {
    const initialHours = hours === null ? '' : formatDecimal(hours);

    return {
        url,
        week,
        hours: initialHours,
        description: description ?? '',
        savedHours: initialHours,
        savedDescription: description ?? '',
        isSaving: false,
        error: null,

        get isFilled() {
            return parseHours(this.hours) > 0;
        },

        isDirty() {
            return this.hours !== this.savedHours || this.description !== this.savedDescription;
        },

        leave(event) {
            if (this.$root.contains(event.relatedTarget) || ! this.isDirty()) {
                return;
            }

            this.save();
        },

        async save() {
            this.isSaving = true;
            this.error = null;

            try {
                const response = await fetch(this.url, {
                    method: 'PUT',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ hours: this.hours, description: this.description }),
                });
                const data = await response.json();

                if (! response.ok) {
                    this.error = firstErrorMessage(data);

                    return;
                }

                this.hours = data.entry ? formatDecimal(data.entry.hours) : '';
                this.description = data.entry ? data.entry.description : '';
                this.savedHours = this.hours;
                this.savedDescription = this.description;

                this.weekHours[this.week] = data.weekHours;
                this.monthHours = data.monthHours;
                this.monthAmount = data.monthAmount;

                if (data.approvalStatus !== this.approvalStatus) {
                    window.location.reload();
                }
            } catch {
                this.error = 'Opslaan mislukt. Controleer je verbinding.';
            } finally {
                this.isSaving = false;
            }
        },
    };
}
