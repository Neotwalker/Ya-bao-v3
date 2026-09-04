import { catalogConfig } from '../../config.catalog.js';

function readCell(cell) {
  if (!cell) return '';
  if (cell.v !== null && cell.v !== undefined) return cell.v;
  return cell.f ?? '';
}

function parseGvizTable(response) {
  if (!response || response.status === 'error' || !response.table) {
    throw new Error(
      response?.errors?.[0]?.detailed_message || 'Google Sheets не вернул таблицу.',
    );
  }

  const cols = response.table.cols || [];
  const rows = (response.table.rows || []).map(row => (row.c || []).map(readCell));
  let headers = cols.map((col, index) =>
    String(col?.label || col?.id || `column_${index + 1}`).trim()
  );

  const labelsAreTechnical = headers.every(header =>
    /^([A-Z]|column_\d+)$/i.test(header)
  );

  if (labelsAreTechnical && rows.length) {
    const candidate = rows[0].map(value => String(value ?? '').trim());
    const textCells = candidate.filter(Boolean).length;

    if (textCells >= 2) {
      headers = candidate;
      rows.shift();
    }
  }

  return rows
    .filter(row => row.some(value => String(value ?? '').trim() !== ''))
    .map(row => Object.fromEntries(
      headers.map((header, index) => [
        header || `column_${index + 1}`,
        row[index] ?? '',
      ]),
    ));
}

function loadSheetWithScript(sheetName) {
  return new Promise((resolve, reject) => {
    const callbackOwner = window.google = window.google || {};
    callbackOwner.visualization = callbackOwner.visualization || {};
    callbackOwner.visualization.Query = callbackOwner.visualization.Query || {};

    const previousHandler = callbackOwner.visualization.Query.setResponse;
    const script = document.createElement('script');
    let completed = false;

    const finish = (error, value) => {
      if (completed) return;
      completed = true;
      window.clearTimeout(timeout);
      script.remove();
      callbackOwner.visualization.Query.setResponse = previousHandler;
      error ? reject(error) : resolve(value);
    };

    const timeout = window.setTimeout(
      () => finish(new Error(`Лист «${sheetName}» не ответил вовремя.`)),
      catalogConfig.requestTimeoutMs,
    );

    callbackOwner.visualization.Query.setResponse = response => {
      try {
        finish(null, parseGvizTable(response));
      } catch (error) {
        finish(error);
      }
    };

    script.onerror = () => finish(new Error(`Не удалось загрузить лист «${sheetName}».`));

    const params = new URLSearchParams({
      tqx: 'out:json',
      sheet: sheetName,
      headers: '1',
    });

    script.src = `https://docs.google.com/spreadsheets/d/${catalogConfig.spreadsheetId}/gviz/tq?${params.toString()}`;
    script.async = true;
    document.head.append(script);
  });
}

export async function loadSheets() {
  const result = {};

  for (const sheetName of catalogConfig.sheets) {
    try {
      result[sheetName] = await loadSheetWithScript(sheetName);
    } catch (error) {
      console.warn(`[catalog] ${sheetName}:`, error.message);
      result[sheetName] = [];
    }
  }

  if (!Object.values(result).some(rows => rows.length)) {
    throw new Error('Не удалось получить данные ни из одного листа.');
  }

  return result;
}
