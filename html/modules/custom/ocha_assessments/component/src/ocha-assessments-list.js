import { html, css } from 'lit-element';
import {unsafeHTML} from 'lit-html/directives/unsafe-html.js';
import { OchaAssessmentsBase } from './ocha-assessments-base.js';
import { tableStyles } from './ocha-assessments-styles.js';

// Extend the LitElement base class
class OchaAssessmentsList extends OchaAssessmentsBase {
  static get styles() {
    return [
      super.styles,
      tableStyles,
      css`
        :host { display: block;
          border: 1px solid purple;
        }`
    ]
  }

  buildDocument(prefix, data, title) {
    switch (data[prefix + '_accessibility']) {
      case 'Publicly Available':
        if (data[prefix + '_file_url']) {
          return html`
            <div class="assessment-document">
              <div class="assessment-document-title">${title}</div>
              <a class="assessment-document-link" href="${this.baseurl}/${data[prefix + '_file_url']}">${data[prefix + '_description']}</a>
            </div>
          `;
        }
        break;

      case 'Available on Request':
        return html`
          <div class="assessment-document">
            <div class="assessment-document-title">${title}</div>
            <p>Available on Request.</p>
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

      ${this.renderPager()}

      <div class="filters">
        ${
          dropdowns.map(
            d => this.renderDropdown(d)
          )
        }

        <button @click="${this.resetData}">Reset</button>
      </div>

      <ul class="cd-list">
        ${
          this.data.map(
            r =>
              html`
                <li>
                  <h2><a href="${this.baseurl}/node/${r.nid}">${r.title}</a></h2>
                  <div>
                    <p>
                      <span class="label">Leading/Coordinating Organization(s): </span>
                      <span class="values">${unsafeHTML(r.field_asst_organizations_label)}</span>
                    </p>
                    <p>
                      <span class="label">Status: </span>
                      <span class="values">${r.field_status}</span>
                    </p>
                    <p>
                      <span class="label">Assessment Date(s): </span>
                      <span class="values">${this.renderDate(r)}</span>
                    </p>
                  </div>
                </li>
                `
        )}
      </ul>
    `;
  }

  connectedCallback() {
    super.connectedCallback();
  }

}

customElements.define('ocha-assessments-list', OchaAssessmentsList);

