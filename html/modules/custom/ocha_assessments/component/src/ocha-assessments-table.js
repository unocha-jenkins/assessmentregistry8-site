import { html } from 'lit-element';
import { OchaAssessmentsBase } from './ocha-assessments-base.js';

// Extend the LitElement base class
class OchaAssessmentsTable extends OchaAssessmentsBase {
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
                    <td>${r.title}</td>
                    <td>${r.field_locations_label}</td>
                    <td>${r.field_organizations_label}</td>
                    <td>${r.field_asst_organizations_label}</td>
                    <td>${r.field_local_groups_label}</td>
                    <td>${r.field_status}</td>
                    <td>${this.renderDate(r)}</td>
                    <td>${r.field_locations_label}</td>
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

