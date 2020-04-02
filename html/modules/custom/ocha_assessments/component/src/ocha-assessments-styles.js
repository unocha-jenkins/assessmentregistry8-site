import { css } from 'lit-element';

export const typography = css`
  :host {
    font-size: var(--cd-font-size-base);
    font-family: var(--cd-font);
    color: var(--cd-dark-grey);
  }
`;

export const buttonStyles = css`
  .blue-button {
    color: white;
    background-color: blue;
  }
  .blue-button:disabled {
    background-color: grey;
  }
`;

export const tableStyles = css`
  .cd-table {
    margin: 0 auto 3rem;
    border-collapse: collapse;
    width: 100%;
    empty-cells: hide;
  }

  th,
  td {
    padding: 0.5rem;
    text-align: left;
  }

  th {
    color: var(--cd-ocha-blue);
    border-bottom: 1px solid white;
    background: var(--cd-site-bg-color);
  }

  .cd-table a {
    word-break: break-word;
  }

  @media (min-width: 576px) {
    th[data-sort-type="numeric"],
    .cd-table--amount,
    .cd-table--amount-total {
      text-align: right;
    }
  }

  tfoot {
    font-weight: bold;
  }

  /* Row numbers */
  .cd-table--row-numbers {
    counter-reset: rowNumber;
  }

  .cd-table--row-numbers tbody tr {
    counter-increment: rowNumber;
  }

  .cd-table--row-numbers tbody tr td.cd-table--row-num:first-child::before {
    content: counter(rowNumber);
    min-width: 1em;
    margin-right: 0.5em;
    font-weight: normal;
  }

  /* Striping */
  .cd-table--striped tr:nth-child(odd) {
    background: white;
  }

  .cd-table--striped tr:nth-child(even) {
    background: var(--cd-light-grey);
  }

  @media (max-width: 575px) {
    /* Force table to not be like tables anymore */
    table,
    thead,
    tbody,
    tfoot,
    th,
    td,
    tr {
      display: block;
    }

    /* Hide table headers (but not display: none;, for accessibility) */
    thead tr {
      position: absolute;
      top: -9999px;
      left: -9999px;
    }

    tr {
      border-bottom: 1px solid var(--cd-light-grey);
      padding: 0 !important;
    }

    td {
      /* Behave  like a "row" */
      border: none;
      border-bottom: 1px solid var(--cd-site-bg-color);
      position: relative;
      padding: 0.5rem;
      padding-left: 40% !important;
      min-height: 2rem;
      white-space: normal !important;
      text-align: left;
    }

    td:empty {
      border-bottom: none;
      padding: 0;
      min-height: unset;
    }

    td:before {
      position: absolute;
      top: 0.5rem;
      left: 0.5rem;
      width: 35%;
      padding-right: 1rem;
      text-align: left;
      font-weight: bold;
      font-size: 0.85rem;
      color: var(--cd-ocha-blue);
      /* Label the data */
      content: attr(data-content);
    }

    td:empty:before {
      content: none;
    }

    .cd-table--row-numbers tbody tr td.cd-table--row-num {
      height: 3rem;
    }

    .cd-table--row-numbers tbody tr td.cd-table--row-num::before {
      font-weight: bold;
      font-size: 1.5rem;
    }

    tfoot td {
      border-bottom: 0 none;
    }
  }
`;
