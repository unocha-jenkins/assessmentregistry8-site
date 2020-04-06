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
  .cd-button {
    -webkit-appearance: none;
    border-radius: 0;
    box-shadow: none;
    border: 0;
    padding: 1rem;
    font-size: 1rem;
    font-weight: bold;
    transition: background 0.3s ease;
    width: auto;
  }

  .cd-button--icon {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .cd-button:hover,
  .cd-button:focus {
    background-color: rbga(0, 0 ,0, 0.1);
  }

  .cd-button:focus {
    outline: 3px solid var(--cd-ocha-blue);
  }

  .cd-button--style {
    text-transform: uppercase;
    background: var(--cd-ocha-blue);
    color: var(--cd-white);
  }

  .cd-button--style:hover,
  .cd-button--style:focus {
    background: var(--cd-dark-blue);
    color: var(--cd-white);
  }

  /* Some buttons have SVG icons */
  .cd-button--icon svg {
    fill: var(--cd-white);
    width: 2rem;
    height: 2rem;
    /* Icon before */
    padding: 0 0.5rem 0 0;
  }

  .cd-button--icon span + svg {
  /* Icon after */
    padding: 0 0 0 0.5rem;
  }

  .cd-button--icon:hover svg,
  .cd-button--icon:focus svg {
    fill: var(--cd-white);
  }

  .cd-button--export {
    background: var(--cd-mid-grey);
    color: var(--cd-white);
  }

  .cd-button--export:hover,
  .cd-button--export:focus {
    background: var(--cd-dark-grey);
  }
`;

export const dropdownStyles = css`
  .dropdown {
    min-width: 10em;
  }

  .cd-filters {
    margin-bottom: 1rem;
  }

  .cd-filters__form {
    display: block;
    background: var(--cd-light-grey);
    padding: 1rem;
  }

  .cd-filters__form label {
    display: block;
    margin: 0 0 0.5rem;
  }

  .cd-filters__group {
    margin: 0 0 1rem;
  }

  .cd-filters__form select {
    width: 100%;
    max-width: 100%;
  }

  @media (min-width: 768px) {
    .cd-filters {
      margin-bottom: 2rem;
    }

    .cd-filters__form form {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-start;
    }

    .cd-filters__group {
      padding-right: 1rem;
    }

    /* Quantity queries */
    /* https://alistapart.com/article/quantity-queries-for-css */

    /* If one element */
    .cd-filters__group:only-of-type,
    /* If two elements */
    .cd-filters__group:nth-last-child(2):first-child,
    .cd-filters__group:nth-last-child(2):first-child ~ .cd-filters__group,
    .cd-filters__group:nth-last-child(2):first-child ~ .cd-export,
    /* If three elements */
    .cd-filters__group:nth-last-child(3):first-child,
    .cd-filters__group:nth-last-child(3):first-child ~ .cd-filters__group,
    .cd-filters__group:nth-last-child(3):first-child ~ .cd-export {
      flex: 1 0 33%;
      max-width: 33%;
    }

    .cd-filters__group:nth-last-child(2):first-child ~ .cd-filters__group:last-child,
    .cd-filters__group:nth-last-child(3):first-child ~ .cd-filters__group:last-child {
      padding-right: 0;
    }

    /* If four elements */
    .cd-filters__group:nth-last-child(4):first-child,
    .cd-filters__group:nth-last-child(4):first-child ~ .cd-filters__group,
    .cd-filters__group:nth-last-child(4):first-child ~ .cd-export {
      flex: 1 0 25%;
      max-width: 25%;
    }

    .cd-filters__group:nth-last-child(4):first-child ~ .cd-filters__group:last-child {
      padding-right: 0;
    }

    /* If five elements */
    .cd-filters__group:nth-last-child(5):first-child,
    .cd-filters__group:nth-last-child(5):first-child ~ .cd-filters__group,
    .cd-filters__group:nth-last-child(5):first-child ~ .cd-export {
      flex: 1 0 20%;
      max-width: 20%;
    }

    .cd-filters__group:nth-last-child(5):first-child ~ .cd-filters__group:last-child {
      padding-right: 0;
    }
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
