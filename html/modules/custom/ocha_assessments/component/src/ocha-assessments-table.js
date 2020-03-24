// Import the LitElement base class and html helper function
import { LitElement, html } from 'lit-element';

// Extend the LitElement base class
class OchaAssessmentsTable extends LitElement {
  constructor() {
    super();
    this.data = void 0;
  }

  static get properties() {
    return {
      src: {
        type: String
      },
      data: {
        type: Array
      }
    };
  }

  render() {
    if (!this.data) {
      return html`
        <div>Loading...</div>
      `;
    }

    return html`
      <p>${this.src}</p>
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
                    <td>${r.field_ass_date} - ${r.field_ass_date_end_date}</td>
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
    if (this.src) {
      this.fetchData();
    }
    else {
      console.error('src attribute is required.')
    }
  }

  fetchData() {
    fetch(this.src)
      .then(res => res.json())
      .then(response => {
        this.data = response.search_results;
      })
      .catch(error => console.error("Error fetching data:", error));
  }
}

customElements.define('ocha-assessments-table', OchaAssessmentsTable);

