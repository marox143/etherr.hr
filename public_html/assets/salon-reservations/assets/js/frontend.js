(function () {
  function formDataToJson(form) {
    const data = new FormData(form);
    const payload = {};

    for (const [rawKey, value] of data.entries()) {
      const key = rawKey.endsWith('[]') ? rawKey.slice(0, -2) : rawKey;
      if (payload[key] !== undefined) {
        if (!Array.isArray(payload[key])) {
          payload[key] = [payload[key]];
        }
        payload[key].push(value);
      } else {
        payload[key] = value;
      }
    }

    return payload;
  }

  function initForm(form) {
    const service = form.querySelector('[name="service_id"]');
    const employeeChoice = form.querySelector('[name="employee_choice"]');
    const dateInput = form.querySelector('[data-salon-date]');
    const calendar = form.querySelector('[data-salon-calendar]');
    const calendarGrid = form.querySelector('[data-salon-calendar-grid]');
    const calendarMonthLabel = form.querySelector('[data-salon-calendar-month]');
    const calendarPrev = form.querySelector('[data-salon-calendar-prev]');
    const calendarNext = form.querySelector('[data-salon-calendar-next]');
    const slotsContainer = form.querySelector('[data-salon-slots]');
    const message = form.querySelector('[data-salon-message]');
    const firstAvailableButton = form.querySelector('[data-salon-first-available]');
    const employeeIdInput = form.querySelector('[name="employee_id"]');
    const startInput = form.querySelector('[name="start_datetime"]');

    if (!service || !employeeChoice || !slotsContainer || !employeeIdInput || !startInput) {
      return;
    }

    let currentMonth = new Date();
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
    let availableDates = new Set();
    let selectedDate = '';

    function clearSlots() {
      slotsContainer.innerHTML = '';
      startInput.value = '';
      employeeIdInput.value = '';
    }

    function setMessage(text, type) {
      if (!message) return;
      message.textContent = text;
      message.dataset.type = type || '';
    }

    function formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    function sameMonth(a, b) {
      return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth();
    }

    function setCalendarDisabled(disabled) {
      if (!calendar) return;
      calendar.classList.toggle('is-disabled', disabled);
    }

    function renderCalendar() {
      if (!calendarGrid || !calendarMonthLabel) {
        return;
      }

      const monthLabel = currentMonth.toLocaleDateString('hr-HR', { month: 'long', year: 'numeric' });
      calendarMonthLabel.textContent = monthLabel;
      calendarGrid.innerHTML = '';

      const firstDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
      const lastDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0);
      const offset = (firstDay.getDay() + 6) % 7;

      for (let i = 0; i < offset; i += 1) {
        const cell = document.createElement('div');
        cell.className = 'salon-reservations__calendar-cell is-empty';
        calendarGrid.appendChild(cell);
      }

      for (let day = 1; day <= lastDay.getDate(); day += 1) {
        const date = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day);
        const dateStr = formatDate(date);
        const isAvailable = availableDates.has(dateStr);
        const isSelected = selectedDate === dateStr;

        const cell = document.createElement(isAvailable ? 'button' : 'div');
        cell.className = 'salon-reservations__calendar-cell';
        if (isAvailable) {
          cell.classList.add('is-available');
        } else {
          cell.classList.add('is-unavailable');
        }
        if (isSelected) {
          cell.classList.add('is-selected');
        }
        cell.textContent = day;

        if (isAvailable) {
          cell.type = 'button';
          cell.addEventListener('click', () => {
            selectedDate = dateStr;
            if (dateInput) {
              dateInput.value = dateStr;
            }
            renderCalendar();
            fetchSlots();
          });
        }

        calendarGrid.appendChild(cell);
      }
    }

    async function loadAvailability() {
      if (!service.value) {
        availableDates = new Set();
        selectedDate = '';
        if (dateInput) {
          dateInput.value = '';
        }
        setCalendarDisabled(true);
        renderCalendar();
        return;
      }

      setCalendarDisabled(false);

      const firstDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
      const lastDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0);

      const params = new URLSearchParams({
        employee_id: employeeChoice.value,
        service_id: service.value,
        start_date: formatDate(firstDay),
        end_date: formatDate(lastDay),
      });

      try {
        const response = await fetch(`${SalonReservations.restUrl}/availability?${params.toString()}`);
        if (!response.ok) {
          throw new Error('request_failed');
        }
        const data = await response.json();
        availableDates = new Set(data.dates || []);
        renderCalendar();
        if (selectedDate && availableDates.has(selectedDate)) {
          if (dateInput) {
            dateInput.value = selectedDate;
          }
          fetchSlots();
        } else if (selectedDate && !availableDates.has(selectedDate)) {
          selectedDate = '';
          if (dateInput) {
            dateInput.value = '';
          }
          clearSlots();
        }
      } catch (error) {
        availableDates = new Set();
        renderCalendar();
      }
    }

    function extractSlotTime(slotStart) {
      if (!slotStart) return '';
      const match = String(slotStart).match(/T(\d{2}:\d{2})/);
      if (match && match[1]) {
        return match[1];
      }
      const fallback = new Date(slotStart);
      if (!Number.isNaN(fallback.getTime())) {
        return fallback.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
      }
      return '';
    }

    function renderSlots(slots, showEmployee) {
      clearSlots();
      if (!slots.length) {
        slotsContainer.textContent = SalonReservations.i18n.noSlots;
        return;
      }

      slots.forEach((slot) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'salon-reservations__slot';
        button.dataset.value = slot.start;
        button.dataset.employeeId = slot.employee_id;
        button.dataset.employeeName = slot.employee_name || '';

        const timeLabel = extractSlotTime(slot.start);
        button.textContent = showEmployee && slot.employee_name ? `${timeLabel} — ${slot.employee_name}` : timeLabel;

        button.addEventListener('click', () => {
          slotsContainer.querySelectorAll('.salon-reservations__slot').forEach((el) => {
            el.classList.remove('is-selected');
          });
          button.classList.add('is-selected');
          startInput.value = slot.start;
          employeeIdInput.value = slot.employee_id;
          setMessage('');
        });

        slotsContainer.appendChild(button);
      });
    }

    async function fetchSlots() {
      if (!service.value) {
        setMessage(SalonReservations.i18n.selectSlot, 'error');
        clearSlots();
        return;
      }

      if (!dateInput || !dateInput.value) {
        clearSlots();
        return;
      }

      setMessage(SalonReservations.i18n.loading, 'info');
      slotsContainer.textContent = SalonReservations.i18n.loading;

      const params = new URLSearchParams({
        employee_id: employeeChoice.value,
        service_id: service.value,
        start_date: dateInput.value,
      });

      try {
        const response = await fetch(`${SalonReservations.restUrl}/slots?${params.toString()}`);
        if (!response.ok) {
          throw new Error('request_failed');
        }
        const data = await response.json();
        const slots = data.slots || [];
        renderSlots(slots, employeeChoice.value === '0');
        setMessage('');
      } catch (error) {
        slotsContainer.textContent = SalonReservations.i18n.error;
        setMessage(SalonReservations.i18n.error, 'error');
      }
    }

    async function fetchFirstAvailable() {
      if (!service.value) {
        setMessage(SalonReservations.i18n.selectSlot, 'error');
        return;
      }

      setMessage(SalonReservations.i18n.loading, 'info');

      const params = new URLSearchParams({
        employee_id: employeeChoice.value,
        service_id: service.value,
      });

      try {
        const response = await fetch(`${SalonReservations.restUrl}/first-available?${params.toString()}`);
        if (!response.ok) {
          throw new Error('request_failed');
        }
        const data = await response.json();
        if (!data.slot) {
          setMessage(SalonReservations.i18n.noSlots, 'error');
          return;
        }
        const slot = data.slot;
        renderSlots([slot], employeeChoice.value === '0');
        const firstButton = slotsContainer.querySelector('.salon-reservations__slot');
        if (firstButton) {
          firstButton.classList.add('is-selected');
        }
        startInput.value = slot.start;
        employeeIdInput.value = slot.employee_id;
        const dateStr = slot.start ? slot.start.slice(0, 10) : '';
        if (dateStr) {
          selectedDate = dateStr;
          if (dateInput) {
            dateInput.value = dateStr;
          }
          const slotMonth = new Date(`${dateStr}T00:00:00`);
          if (!sameMonth(slotMonth, currentMonth)) {
            currentMonth = new Date(slotMonth.getFullYear(), slotMonth.getMonth(), 1);
          }
          await loadAvailability();
        }
        setMessage(SalonReservations.i18n.firstAvailable, 'success');
      } catch (error) {
        setMessage(SalonReservations.i18n.error, 'error');
      }
    }

    service.addEventListener('change', () => {
      clearSlots();
      selectedDate = '';
      if (dateInput) {
        dateInput.value = '';
      }
      loadAvailability();
    });

    employeeChoice.addEventListener('change', () => {
      clearSlots();
      loadAvailability();
    });

    if (calendarPrev) {
      calendarPrev.addEventListener('click', () => {
        currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
        loadAvailability();
      });
    }

    if (calendarNext) {
      calendarNext.addEventListener('click', () => {
        currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
        loadAvailability();
      });
    }

    if (firstAvailableButton) {
      firstAvailableButton.addEventListener('click', fetchFirstAvailable);
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!startInput.value || !employeeIdInput.value) {
        setMessage(SalonReservations.i18n.selectSlot, 'error');
        return;
      }

      const payload = formDataToJson(form);
      payload.employee_id = employeeIdInput.value;
      payload.start_datetime = startInput.value;

      try {
        const response = await fetch(`${SalonReservations.restUrl}/reservations`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Salon-Nonce': SalonReservations.nonce,
            'X-WP-Nonce': SalonReservations.restNonce,
          },
          credentials: 'same-origin',
          body: JSON.stringify(payload),
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(data.message || SalonReservations.i18n.submitError);
        }

        setMessage(SalonReservations.i18n.submitSuccess, 'success');
        form.reset();
        clearSlots();
      } catch (error) {
        setMessage(error.message || SalonReservations.i18n.submitError, 'error');
      }
    });

    loadAvailability();
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-salon-form="reservation"]').forEach(initForm);
  });
})();
