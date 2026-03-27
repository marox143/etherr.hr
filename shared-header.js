(function initSharedHeaderRenderer() {
  function renderSharedHeader() {
  const mount = document.querySelector("header[data-shared-header]");
  if (!(mount instanceof HTMLElement)) {
    return;
  }

  const page = document.body?.dataset?.page || "home";
  const isHome = page === "home";
  const isProjects = page === "projects";
  const isAbout = page === "about";

  const links =
    page === "home"
      ? {
          home: "#top",
          about: "about.html",
          services: "#services",
          projects: "projekti.html",
          contact: "#contact",
        }
      : page === "projects"
        ? {
            home: "/",
            about: "about.html",
            services: "/#services",
            projects: "projekti.html",
            contact: "/#contact",
          }
        : {
            home: "/",
            about: "about.html",
            services: "/#services",
            projects: "projekti.html",
            contact: "/#contact",
          };

  mount.innerHTML = `
    <div class="container header-inner">
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="headerPanel">
        <span class="menu-icon" aria-hidden="true"></span>
        <span class="sr-only" data-i18n="menu.toggle"></span>
      </button>
      <a class="header-center-logo" href="${links.home}" aria-label="Etherr">
        <img src="assets/images/logo.png" alt="Etherr logo" width="52" height="52" />
      </a>

      <div class="header-panel" id="headerPanel">
        <nav class="site-nav" aria-label="Primary navigation">
          <a class="nav-home-link" href="${links.home}"${isHome ? ' aria-current="page"' : ""}>
            <span class="nav-home-icon" aria-hidden="true"></span>
            <span class="sr-only" data-i18n="nav.home"></span>
          </a>
          <a href="${links.services}" data-i18n="nav.services"></a>
          <a href="${links.projects}" data-i18n="nav.projects"${isProjects ? ' aria-current="page"' : ""}></a>
          <a href="${links.about}" data-i18n="nav.about"${isAbout ? ' aria-current="page"' : ""}></a>
          <a href="${links.contact}" data-i18n="nav.contact"></a>
        </nav>
        <div class="lang-switch" role="group" aria-label="Language switcher">
          <button class="lang-btn is-active" type="button" data-lang="hr">HR</button>
          <button class="lang-btn" type="button" data-lang="en">EN</button>
          <button class="lang-btn" type="button" data-lang="de">DE</button>
        </div>
      </div>
    </div>
  `;
  }

  window.renderSharedHeader = renderSharedHeader;
  renderSharedHeader();
})();
