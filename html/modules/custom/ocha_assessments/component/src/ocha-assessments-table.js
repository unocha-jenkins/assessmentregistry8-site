import { html, css } from 'lit-element';
import { OchaAssessmentsBase } from './ocha-assessments-base.js';
import { tableStyles } from './ocha-assessments-styles.js';

// Extend the LitElement base class
class OchaAssessmentsTable extends OchaAssessmentsBase {
  static get styles() {
    return [
      super.styles,
      tableStyles,
      css`
        :host { display: block;
          border: 1px solid red;
        }`
    ]
  }

  buildDocument(prefix, data, title) {
    switch (data[prefix + '_accessibility']) {
      case 'Publicly Available':
        if (data[prefix + '_file_url']) {
          return html`
            <div class="assessment-document">
              <h2>${title}</h2>
              <a href="${this.baseurl}/${data[prefix + '_file_url']}">${data[prefix + '_description']}</a>
            </div>
          `;
        }
        break;

      case 'Available on Request':
        return html`
          <div class="assessment-document">
            <h2>${title}</h2>
            Available on Request
            <p>${data[prefix + '_instructions']}</p>
          </div>
        `;

    }
  }

  render() {
    if (!this.data) {
      return html`
        <div>Loading...</div>
      `;
    }

    // Build facets.
    let dropdowns = this.buildFacets();

    return html`
      <p>Source (debug): ${this.src}</p>

      <div class="pager">
        ${this.pager.current_page + 1} / ${this.pager.total_pages}
      </div>

      <div class="filters">
        ${
          dropdowns.map(
            d => this.renderDropdown(d)
          )
        }

        <button @click="${this.resetData}">Reset</button>
      </div>
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Location(s)</th>
            <th>Managed by</th>
            <th>Participating Organization(s)</th>
            <th>Clusters/Sectors</th>
            <th>Status</th>
            <th>Assessment Date(s)</th>
            <th>Data</th>
          </tr>
        </thead>
        <tbody>
          ${
            this.data.map(
              r =>
                html`
                  <tr>
                    <td><a href="${this.baseurl}/node/${r.nid}">${r.title}</a></td>
                    <td>${r.field_locations_label}</td>
                    <td>${r.field_organizations_label}</td>
                    <td>${r.field_asst_organizations_label}</td>
                    <td>${r.field_local_groups_label}</td>
                    <td>${r.field_status}</td>
                    <td>${this.renderDate(r)}</td>
                    <td>${this.buildDocument('data', r, 'Data')}</td>
                  </tr>
                  `
          )}
        </tbody>
      </table>
    `;
  }

  connectedCallback() {
    super.connectedCallback();
  }

}

customElements.define('ocha-assessments-table', OchaAssessmentsTable);

