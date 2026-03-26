(function initRippleData(global) {
  "use strict";

  const STORAGE_KEY = "ripple_project_data_demo_v2";
  const MONTH_MIN = 1;
  const MONTH_MAX = 48;

  const STATUS_VALUES = [
    "not_started",
    "in_progress",
    "at_risk",
    "blocked",
    "done"
  ];

  const DEFAULT_DATA = {
    meta: {
      project: "ATLASFLOW",
      months: 48,
      updatedAt: null
    },
    wps: [
      {
        id: "WP1",
        title: "Stakeholder Discovery & Alignment",
        lead: "KVR",
        total: 96,
        startMonth: 1,
        endMonth: 36,
        status: "done",
        description: "Define collaboration model, governance scope, and shared objectives across participating organizations."
      },
      {
        id: "WP2",
        title: "Solution Design & Prototype Delivery",
        lead: "HZN",
        total: 96,
        startMonth: 1,
        endMonth: 30,
        status: "in_progress",
        description: "Co-design and validate practical solution sets, then prepare them for controlled rollout."
      },
      {
        id: "WP3",
        title: "Training & Capability Program",
        lead: "BRC",
        total: 107,
        startMonth: 1,
        endMonth: 48,
        status: "in_progress",
        description: "Build and refine operational training tracks for internal teams and partner stakeholders."
      },
      {
        id: "WP4",
        title: "Playbook & Replication Framework",
        lead: "ALP",
        total: 124,
        startMonth: 1,
        endMonth: 48,
        status: "at_risk",
        description: "Package methods, templates, and rollout guidance into a reusable implementation toolkit."
      },
      {
        id: "WP5",
        title: "Monitoring Platform & Data Services",
        lead: "FMS",
        total: 116,
        startMonth: 6,
        endMonth: 48,
        status: "blocked",
        description: "Deliver analytics dashboarding, system interoperability, and measurable progress tracking."
      },
      {
        id: "WP6",
        title: "Pilot Execution Program",
        lead: "CDR",
        total: 143,
        startMonth: 12,
        endMonth: 48,
        status: "in_progress",
        description: "Execute and validate interventions in regional pilot environments under live operating constraints."
      },
      {
        id: "WP7",
        title: "Communication & Adoption",
        lead: "JTL",
        total: 107,
        startMonth: 1,
        endMonth: 48,
        status: "not_started",
        description: "Support visibility, stakeholder onboarding, and practical adoption of validated outcomes."
      },
      {
        id: "WP8",
        title: "Program Coordination & Governance",
        lead: "ALP",
        total: 72,
        startMonth: 1,
        endMonth: 48,
        status: "in_progress",
        description: "Run governance, quality control, compliance, and strategic alignment across all streams."
      }
    ],
    partners: [
      { id: "ALP", name: "Alpine Nexus", long: "Alpine Nexus Consulting", group: "TIP", pm: [7, 4, 9, 13, 6, 5, 14, 18] },
      { id: "BRC", name: "BrightCore Labs", long: "BrightCore Labs Ltd.", group: "TEP", pm: [9, 11, 13, 8, 6, 12, 5, 3] },
      { id: "CDR", name: "Cedar Bridge", long: "Cedar Bridge Operations", group: "TEP", pm: [6, 8, 7, 6, 6, 11, 4, 3] },
      { id: "DYN", name: "Dynaris Research", long: "Dynaris Applied Research", group: "TIP", pm: [5, 2, 7, 9, 18, 5, 3, 3] },
      { id: "EKO", name: "EkoVista Systems", long: "EkoVista Systems GmbH", group: "TEP", pm: [10, 8, 6, 7, 9, 19, 10, 5] },
      { id: "FMS", name: "FormaSignal Tech", long: "FormaSignal Technologies", group: "TIP", pm: [2, 1, 9, 7, 19, 4, 3, 3] },
      { id: "GRT", name: "GridRoot Digital", long: "GridRoot Digital Studio", group: "TIP", pm: [4, 5, 7, 7, 3, 10, 5, 3] },
      { id: "HZN", name: "Horizon Foundry", long: "Horizon Foundry Collective", group: "TIP", pm: [4, 12, 11, 10, 3, 15, 9, 5] },
      { id: "ION", name: "Ionera Analytics", long: "Ionera Analytics Group", group: "TIP", pm: [2, 1, 3, 5, 14, 3, 3, 3] },
      { id: "JTL", name: "Jetline Studio", long: "Jetline Studio Network", group: "TIP", pm: [6, 9, 9, 7, 2, 11, 15, 5] },
      { id: "KVR", name: "Kavira Social Lab", long: "Kavira Social Innovation Lab", group: "TEP", pm: [18, 4, 2, 10, 2, 5, 6, 3] },
      { id: "LMX", name: "LumenAxis Regional", long: "LumenAxis Regional Office", group: "TEP", pm: [7, 8, 7, 8, 7, 18, 9, 5] },
      { id: "MTR", name: "Metroline Innovation", long: "Metroline Innovation Institute", group: "TIP", pm: [4, 9, 5, 8, 2, 2, 10, 3] },
      { id: "NRG", name: "Nordgrid Hub", long: "Nordgrid Community Hub", group: "TEP", pm: [5, 7, 2, 9, 10, 3, 5, 3] },
      { id: "OPL", name: "Opal Teamworks", long: "Opal Teamworks Europe", group: "TEP", pm: [4, 5, 7, 7, 6, 16, 7, 5] },
      { id: "PRM", name: "Primegate Lab", long: "Primegate University Lab", group: "TIP", pm: [3, 2, 3, 3, 3, 4, 4, 3] }
    ],
    tasks: [
      {
        id: "T1.1",
        wpId: "WP1",
        label: "Stakeholder participation",
        description: "Participatory diagnosis and engagement with pilot site stakeholders.",
        lead: "CDR",
        participants: ["BRC", "KVR", "HZN", "MTR", "PRM", "LMX"],
        startMonth: 1,
        endMonth: 12,
        status: "done"
      },
      {
        id: "T1.2",
        wpId: "WP1",
        label: "Stakeholder collaboration framework",
        description: "Define collaboration structures, inclusion rules, and operational governance.",
        lead: "KVR",
        participants: ["FMS", "CDR", "ION", "MTR", "NRG"],
        startMonth: 3,
        endMonth: 12,
        status: "in_progress"
      },
      {
        id: "T1.3",
        wpId: "WP1",
        label: "Citizen engagement strategies",
        description: "Run citizen science protocols, awareness campaigns, and engagement tactics.",
        lead: "KVR",
        participants: ["BRC", "CDR", "HZN", "EKO", "LMX", "OPL", "JTL", "NRG", "PRM"],
        startMonth: 6,
        endMonth: 36,
        status: "in_progress"
      },
      {
        id: "T2.1",
        wpId: "WP2",
        label: "Mapping existing open-source solutions",
        description: "Screen existing low-tech/open-source solutions for project relevance.",
        lead: "BRC",
        participants: ["HZN", "GRT", "JTL", "OPL"],
        startMonth: 1,
        endMonth: 12,
        status: "done"
      },
      {
        id: "T2.2",
        wpId: "WP2",
        label: "Co-design sessions in makerspaces/fab-labs",
        description: "Facilitate collaborative design workshops with stakeholders and makerspaces.",
        lead: "HZN",
        participants: ["BRC", "CDR", "JTL", "NRG", "OPL"],
        startMonth: 6,
        endMonth: 18,
        status: "in_progress"
      },
      {
        id: "T2.3",
        wpId: "WP2",
        label: "Prototyping and pilot testing",
        description: "Build and test selected open-source prototypes under pilot conditions.",
        lead: "JTL",
        participants: ["HZN", "BRC", "GRT", "NRG", "OPL"],
        startMonth: 6,
        endMonth: 18,
        status: "blocked"
      },
      {
        id: "T2.4",
        wpId: "WP2",
        label: "Circular transformation pilots",
        description: "Pilot circular business and transformation actions with local actors.",
        lead: "MTR",
        participants: ["BRC", "CDR", "KVR", "HZN", "NRG", "OPL"],
        startMonth: 12,
        endMonth: 24,
        status: "at_risk"
      },
      {
        id: "T2.5",
        wpId: "WP2",
        label: "Integration with removal technologies and procurement templates",
        description: "Prepare integration pathways and procurement-ready templates.",
        lead: "HZN",
        participants: ["BRC", "EKO", "OPL", "LMX", "NRG", "JTL"],
        startMonth: 12,
        endMonth: 30,
        status: "not_started"
      },
      {
        id: "T3.1",
        wpId: "WP3",
        label: "Education framework design",
        description: "Define the structure and learning outcomes for sustainability training.",
        lead: "BRC",
        participants: ["HZN", "GRT", "MTR"],
        startMonth: 1,
        endMonth: 12,
        status: "done"
      },
      {
        id: "T3.2",
        wpId: "WP3",
        label: "Development of training modules",
        description: "Create and package training modules for core target groups.",
        lead: "GRT",
        participants: ["BRC", "HZN", "DYN", "FMS", "OPL"],
        startMonth: 6,
        endMonth: 18,
        status: "in_progress"
      },
      {
        id: "T3.3",
        wpId: "WP3",
        label: "Evaluation and iteration of education programs",
        description: "Iterate education modules using pilot evidence and evaluation feedback.",
        lead: "HZN",
        participants: ["BRC", "GRT", "MTR", "PRM", "ALP", "OPL"],
        startMonth: 18,
        endMonth: 48,
        status: "at_risk"
      },
      {
        id: "T4.1",
        wpId: "WP4",
        label: "Toolbox architecture and content design",
        description: "Design toolbox structure and content model for practical use.",
        lead: "BRC",
        participants: ["HZN", "KVR", "MTR"],
        startMonth: 1,
        endMonth: 18,
        status: "in_progress"
      },
      {
        id: "T4.2",
        wpId: "WP4",
        label: "Compilation of guides, playbooks and methodologies",
        description: "Compile validated methods, guides, and operational playbooks.",
        lead: "ALP",
        participants: ["GRT", "BRC", "DYN", "MTR", "PRM"],
        startMonth: 6,
        endMonth: 18,
        status: "done"
      },
      {
        id: "T4.3",
        wpId: "WP4",
        label: "Replication and scalability framework",
        description: "Define replication pathways and scale-up framework for wider uptake.",
        lead: "ALP",
        participants: ["CDR", "EKO", "LMX", "JTL"],
        startMonth: 36,
        endMonth: 48,
        status: "not_started"
      },
      {
        id: "T4.4",
        wpId: "WP4",
        label: "Integration of digital tools and dashboards",
        description: "Integrate decision support dashboards into the toolbox ecosystem.",
        lead: "FMS",
        participants: ["ION", "NRG"],
        startMonth: 18,
        endMonth: 42,
        status: "blocked"
      },
      {
        id: "T4.5",
        wpId: "WP4",
        label: "Toolbox validation with stakeholders",
        description: "Validate toolbox usability and relevance with external stakeholders.",
        lead: "CDR",
        participants: ["BRC", "KVR", "HZN", "FMS", "JTL", "MTR"],
        startMonth: 30,
        endMonth: 48,
        status: "in_progress"
      },
      {
        id: "T5.1",
        wpId: "WP5",
        label: "Digital tools and dashboards",
        description: "Develop and deploy dashboards and core digital monitoring services.",
        lead: "FMS",
        participants: ["ION", "NRG"],
        startMonth: 6,
        endMonth: 36,
        status: "done"
      },
      {
        id: "T5.2",
        wpId: "WP5",
        label: "Digital platform and toolbox integration",
        description: "Connect platform capabilities with toolbox processes and outputs.",
        lead: "FMS",
        participants: ["ION", "NRG"],
        startMonth: 9,
        endMonth: 42,
        status: "in_progress"
      },
      {
        id: "T5.3",
        wpId: "WP5",
        label: "Monitoring protocols and baseline assessment",
        description: "Establish baseline indicators and protocolized monitoring setup.",
        lead: "DYN",
        participants: ["HZN", "BRC", "EKO", "OPL", "NRG", "LMX", "PRM"],
        startMonth: 6,
        endMonth: 24,
        status: "at_risk"
      },
      {
        id: "T5.4",
        wpId: "WP5",
        label: "Endline monitoring and impact validation",
        description: "Complete endline monitoring and quantify intervention impact.",
        lead: "DYN",
        participants: ["HZN", "BRC", "EKO", "OPL", "NRG", "LMX", "PRM"],
        startMonth: 23,
        endMonth: 45,
        status: "not_started"
      },
      {
        id: "T6.1",
        wpId: "WP6",
        label: "Pilot preparation and site selection",
        description: "Prepare local governance and select pilot deployment sites.",
        lead: "OPL",
        participants: ["CDR", "HZN", "EKO", "LMX", "PRM"],
        startMonth: 18,
        endMonth: 24,
        status: "done"
      },
      {
        id: "T6.2",
        wpId: "WP6",
        label: "Deployment of removal technologies",
        description: "Deploy and validate removal technologies in pilot contexts.",
        lead: "JTL",
        participants: ["HZN", "GRT", "EKO", "OPL", "LMX"],
        startMonth: 24,
        endMonth: 36,
        status: "in_progress"
      },
      {
        id: "T6.3",
        wpId: "WP6",
        label: "Implementation of prevention actions",
        description: "Run prevention actions including campaigns and waste interventions.",
        lead: "EKO",
        participants: ["CDR", "KVR", "OPL", "LMX"],
        startMonth: 24,
        endMonth: 42,
        status: "at_risk"
      },
      {
        id: "T6.4",
        wpId: "WP6",
        label: "Circular transformation and social entrepreneurship initiatives",
        description: "Implement circular and entrepreneurship pilots based on local needs.",
        lead: "HZN",
        participants: ["JTL", "BRC", "GRT", "OPL", "EKO", "LMX"],
        startMonth: 18,
        endMonth: 48,
        status: "blocked"
      },
      {
        id: "T7.1",
        wpId: "WP7",
        label: "Communication strategy and branding",
        description: "Maintain communication strategy, identity, and branding assets.",
        lead: "JTL",
        participants: "ALL",
        startMonth: 1,
        endMonth: 48,
        status: "in_progress"
      },
      {
        id: "T7.2",
        wpId: "WP7",
        label: "Dissemination to science, industry, policy and civil society",
        description: "Deliver dissemination actions to all core stakeholder segments.",
        lead: "ION",
        participants: "ALL",
        startMonth: 1,
        endMonth: 48,
        status: "in_progress"
      },
      {
        id: "T7.3",
        wpId: "WP7",
        label: "Exploitation strategy",
        description: "Develop exploitation pathways and value realization strategy.",
        lead: "ALP",
        participants: "ALL",
        startMonth: 1,
        endMonth: 48,
        status: "not_started"
      },
      {
        id: "T7.4",
        wpId: "WP7",
        label: "Associated Regions scheme",
        description: "Run open call and support replication with associated regions.",
        lead: "ALP",
        participants: "ALL",
        startMonth: 9,
        endMonth: 48,
        status: "at_risk"
      },
      {
        id: "T8.1",
        wpId: "WP8",
        label: "Overall management and coordination",
        description: "Run project management, reporting, and daily coordination.",
        lead: "ALP",
        participants: "ALL",
        startMonth: 1,
        endMonth: 48,
        status: "done"
      },
      {
        id: "T8.2",
        wpId: "WP8",
        label: "Kick-off and regular project meetings",
        description: "Coordinate project meetings and steering structures.",
        lead: "ALP",
        participants: "ALL",
        startMonth: 1,
        endMonth: 48,
        status: "in_progress"
      },
      {
        id: "T8.3",
        wpId: "WP8",
        label: "Risk and quality management",
        description: "Operate risk monitoring and quality assurance processes.",
        lead: "ALP",
        participants: "ALL",
        startMonth: 1,
        endMonth: 48,
        status: "at_risk"
      },
      {
        id: "T8.4",
        wpId: "WP8",
        label: "Data management and ethics",
        description: "Ensure FAIR/GDPR-aligned data governance and ethics compliance.",
        lead: "ALP",
        participants: ["FMS"],
        startMonth: 1,
        endMonth: 48,
        status: "blocked"
      },
      {
        id: "T8.5",
        wpId: "WP8",
        label: "Strategic coordination with external programs",
        description: "Align project streams with related public and industry initiatives.",
        lead: "ALP",
        participants: "ALL",
        startMonth: 1,
        endMonth: 48,
        status: "in_progress"
      }
    ],
    deliverables: [
      {
        id: "D1.1",
        wpId: "WP1",
        title: "Participatory Diagnosis Report",
        dueMonths: [12],
        status: "not_started",
        description: "Pilot-level participatory findings."
      },
      {
        id: "D1.2",
        wpId: "WP1",
        title: "Stakeholder Collaboration Framework",
        dueMonths: [12],
        status: "not_started",
        description: "Governance and participation framework."
      },
      {
        id: "D1.3",
        wpId: "WP1",
        title: "Citizen Engagement Strategies and Implementation Report",
        dueMonths: [18],
        status: "not_started",
        description: "Citizen science and engagement package."
      },
      {
        id: "D2.1",
        wpId: "WP2",
        title: "Technical Solutions Overview and procurement framework",
        dueMonths: [18],
        status: "not_started",
        description: "Mapping and framework for municipal uptake."
      },
      {
        id: "D2.2",
        wpId: "WP2",
        title: "Technology Assessment and Prototyping Report",
        dueMonths: [18],
        status: "not_started",
        description: "Co-design/prototype validation evidence."
      },
      {
        id: "D3.1",
        wpId: "WP3",
        title: "Education and Training Modules",
        dueMonths: [24],
        status: "not_started",
        description: "Education material package."
      },
      {
        id: "D3.2",
        wpId: "WP3",
        title: "Evaluation of Education and Training Modules",
        dueMonths: [48],
        status: "not_started",
        description: "End-cycle evaluation and refinement."
      },
      {
        id: "D4.1",
        wpId: "WP4",
        title: "Toolbox compilation",
        dueMonths: [24],
        status: "not_started",
        description: "First consolidated toolbox content."
      },
      {
        id: "D4.2",
        wpId: "WP4",
        title: "Toolbox Validation report",
        dueMonths: [48],
        status: "not_started",
        description: "Stakeholder validation of toolbox."
      },
      {
        id: "D5.1",
        wpId: "WP5",
        title: "Digital tools and dashboard architecture specifications",
        dueMonths: [12],
        status: "not_started",
        description: "Architecture and design baseline."
      },
      {
        id: "D5.2",
        wpId: "WP5",
        title: "Final digital tools and dashboards",
        dueMonths: [36],
        status: "not_started",
        description: "Validated predictive dashboards."
      },
      {
        id: "D5.3",
        wpId: "WP5",
        title: "Platform architecture and interoperability plan",
        dueMonths: [24],
        status: "not_started",
        description: "Interoperability with EU infrastructures."
      },
      {
        id: "D5.4",
        wpId: "WP5",
        title: "Final Toolbox digital platform",
        dueMonths: [48],
        status: "not_started",
        description: "Full operational platform."
      },
      {
        id: "D5.5",
        wpId: "WP5",
        title: "Impact assessment report",
        dueMonths: [48],
        status: "not_started",
        description: "Baseline-endline impact evidence."
      },
      {
        id: "D6.1",
        wpId: "WP6",
        title: "Pilot site action plans",
        dueMonths: [12],
        status: "not_started",
        description: "Site and implementation plans."
      },
      {
        id: "D6.2",
        wpId: "WP6",
        title: "Pilot implementation and impact validation report",
        dueMonths: [48],
        status: "not_started",
        description: "Consolidated pilot validation."
      },
      {
        id: "D7.1",
        wpId: "WP7",
        title: "PDEC",
        dueMonths: [6],
        status: "not_started",
        description: "Dissemination, exploitation, and communication plan."
      },
      {
        id: "D7.2",
        wpId: "WP7",
        title: "Communication Toolkit",
        dueMonths: [9],
        status: "not_started",
        description: "Branding and templates."
      },
      {
        id: "D7.3",
        wpId: "WP7",
        title: "ARs Open Call Package",
        dueMonths: [12],
        status: "not_started",
        description: "Associated Regions call package."
      },
      {
        id: "D7.4",
        wpId: "WP7",
        title: "IP and Exploitation Manual",
        dueMonths: [12],
        status: "not_started",
        description: "IP/ownership/exploitation guidance."
      },
      {
        id: "D7.5",
        wpId: "WP7",
        title: "Exploitation, policy briefs and replication guidance",
        dueMonths: [42],
        status: "not_started",
        description: "Policy and uptake package."
      },
      {
        id: "D7.6",
        wpId: "WP7",
        title: "Final Dissemination and Exploitation Report",
        dueMonths: [48],
        status: "not_started",
        description: "Final impact and uptake reporting."
      },
      {
        id: "D8.1",
        wpId: "WP8",
        title: "Project management plan and timeline",
        dueMonths: [2],
        status: "not_started",
        description: "Governance and reporting setup."
      },
      {
        id: "D8.2",
        wpId: "WP8",
        title: "Quality Assurance and Risk Plan",
        dueMonths: [3],
        status: "not_started",
        description: "Quality assurance and risk framework."
      },
      {
        id: "D8.3",
        wpId: "WP8",
        title: "Data Management Plan (DMP)",
        dueMonths: [6],
        status: "not_started",
        description: "FAIR data framework."
      },
      {
        id: "D8.4",
        wpId: "WP8",
        title: "Administrative and financial reporting",
        dueMonths: [12, 24, 36, 48],
        status: "not_started",
        description: "Periodic management reporting."
      },
      {
        id: "D8.5",
        wpId: "WP8",
        title: "Final project report",
        dueMonths: [48],
        status: "not_started",
        description: "Final consolidated report."
      }
    ],
    milestones: [
      {
        id: "M1",
        title: "Governance and quality framework established",
        dueMonth: 3,
        wpId: "WP8",
        verification: "D8.1, D8.2, D8.3",
        status: "not_started",
        description: "Management structures and quality rules are active."
      },
      {
        id: "M2",
        title: "Communication and dissemination strategy operational",
        dueMonth: 9,
        wpId: "WP7",
        verification: "D7.1, D7.2",
        status: "not_started",
        description: "Communication assets and campaign channels are live."
      },
      {
        id: "M3",
        title: "Citizen participation baseline completed",
        dueMonth: 12,
        wpId: "WP1",
        verification: "D1.1, D1.2",
        status: "not_started",
        description: "Participation baseline and collaboration framework completed."
      },
      {
        id: "M4",
        title: "Digital tools architecture and pilot baselines validated",
        dueMonth: 12,
        wpId: "WP5",
        verification: "D5.1, D6.1",
        status: "not_started",
        description: "Core architecture and site baselines approved."
      },
      {
        id: "M5",
        title: "Associated Regions open call launched",
        dueMonth: 12,
        wpId: "WP7",
        verification: "D7.3",
        status: "not_started",
        description: "Open call package published and active."
      },
      {
        id: "M6",
        title: "Citizen engagement protocols operational",
        dueMonth: 18,
        wpId: "WP1",
        verification: "D1.3",
        status: "not_started",
        description: "Citizen engagement actions are implemented in pilots."
      },
      {
        id: "M7",
        title: "Technical solutions mapped and prototyped",
        dueMonth: 18,
        wpId: "WP2",
        verification: "D2.1, D2.2",
        status: "not_started",
        description: "Solution portfolio and prototypes validated."
      },
      {
        id: "M8",
        title: "Education modules available",
        dueMonth: 24,
        wpId: "WP3",
        verification: "D3.1",
        status: "not_started",
        description: "Education package is available for target groups."
      },
      {
        id: "M9",
        title: "Toolbox first version compiled",
        dueMonth: 24,
        wpId: "WP4",
        verification: "D4.1",
        status: "not_started",
        description: "First integrated toolbox release is published."
      },
      {
        id: "M10",
        title: "Interoperable platform and monitoring framework ready",
        dueMonth: 24,
        wpId: "WP5",
        verification: "D5.3",
        status: "not_started",
        description: "Interoperable platform and monitoring framework are operational."
      },
      {
        id: "M11",
        title: "Dashboards and predictive tools operational",
        dueMonth: 36,
        wpId: "WP5",
        verification: "D5.2",
        status: "not_started",
        description: "Predictive dashboards are deployed and in use."
      },
      {
        id: "M12",
        title: "Policy briefs and replication guidance prepared",
        dueMonth: 42,
        wpId: "WP7",
        verification: "D7.5",
        status: "not_started",
        description: "Policy and replication documents are available."
      },
      {
        id: "M13",
        title: "Pilot implementation consolidated",
        dueMonth: 48,
        wpId: "WP6",
        verification: "D6.2",
        status: "not_started",
        description: "Pilot results and validation evidence are consolidated."
      },
      {
        id: "M14",
        title: "Final toolbox and digital tools validated and published",
        dueMonth: 48,
        wpId: "WP5",
        verification: "D3.2, D4.2, D5.4",
        status: "not_started",
        description: "Final integrated toolbox and digital platform released."
      },
      {
        id: "M15",
        title: "Project finalized and impact documented",
        dueMonth: 48,
        wpId: "WP8",
        verification: "D7.6, D8.5",
        status: "not_started",
        description: "Final reporting and impact evidence completed."
      }
    ]
  };

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function asString(value, fallback) {
    if (typeof value === "string" && value.trim()) {
      return value.trim();
    }
    return fallback;
  }

  function asNumber(value, fallback) {
    const parsed = Number(value);
    if (Number.isFinite(parsed)) {
      return parsed;
    }
    return fallback;
  }

  function clampMonth(value, fallback) {
    const numeric = Math.round(asNumber(value, fallback));
    return Math.max(MONTH_MIN, Math.min(MONTH_MAX, numeric));
  }

  function normalizeStatus(value) {
    return STATUS_VALUES.includes(value) ? value : "not_started";
  }

  function sanitizeId(value, fallback) {
    return asString(value, fallback).replace(/\s+/g, "");
  }

  function uniqueNumbers(values) {
    const seen = new Set();
    const months = [];
    for (const value of values) {
      const month = clampMonth(value, MONTH_MIN);
      if (!seen.has(month)) {
        seen.add(month);
        months.push(month);
      }
    }
    months.sort((a, b) => a - b);
    return months;
  }

  function normalizeWp(wp, index) {
    const fallback = DEFAULT_DATA.wps[index] || DEFAULT_DATA.wps[0];
    const startMonth = clampMonth(wp && wp.startMonth, fallback.startMonth);
    const endMonth = clampMonth(wp && wp.endMonth, fallback.endMonth);
    return {
      id: sanitizeId(wp && wp.id, fallback.id),
      title: asString(wp && wp.title, fallback.title),
      lead: sanitizeId(wp && wp.lead, fallback.lead),
      total: Math.max(0, Math.round(asNumber(wp && wp.total, fallback.total))),
      startMonth: Math.min(startMonth, endMonth),
      endMonth: Math.max(startMonth, endMonth),
      status: normalizeStatus(wp && wp.status),
      description: asString(wp && wp.description, fallback.description)
    };
  }

  function normalizePartner(partner, wpCount, index) {
    const fallback = DEFAULT_DATA.partners[index] || DEFAULT_DATA.partners[0];
    const normalized = {
      id: sanitizeId(partner && partner.id, fallback.id),
      name: asString(partner && partner.name, fallback.name),
      long: asString(partner && partner.long, fallback.long),
      group: asString(partner && partner.group, fallback.group),
      pm: []
    };

    const inputPm = Array.isArray(partner && partner.pm) ? partner.pm : fallback.pm;
    for (let i = 0; i < wpCount; i += 1) {
      normalized.pm.push(Math.max(0, Math.round(asNumber(inputPm[i], 0))));
    }

    return normalized;
  }

  function normalizeParticipants(participants) {
    if (participants === "ALL") {
      return "ALL";
    }
    if (!Array.isArray(participants)) {
      return [];
    }

    const set = new Set();
    for (const id of participants) {
      const clean = sanitizeId(id, "");
      if (clean) {
        set.add(clean);
      }
    }
    return Array.from(set);
  }

  function normalizeTask(task, index) {
    const fallback = DEFAULT_DATA.tasks[index] || DEFAULT_DATA.tasks[0];
    const startMonth = clampMonth(task && task.startMonth, fallback.startMonth);
    const endMonth = clampMonth(task && task.endMonth, fallback.endMonth);

    return {
      id: sanitizeId(task && task.id, fallback.id),
      wpId: sanitizeId(task && task.wpId, fallback.wpId),
      label: asString(task && task.label, fallback.label),
      description: asString(task && task.description, fallback.description),
      lead: sanitizeId(task && task.lead, fallback.lead),
      participants: normalizeParticipants(task && task.participants),
      startMonth: Math.min(startMonth, endMonth),
      endMonth: Math.max(startMonth, endMonth),
      status: normalizeStatus(task && task.status)
    };
  }

  function normalizeDeliverable(deliverable, index) {
    const fallback = DEFAULT_DATA.deliverables[index] || DEFAULT_DATA.deliverables[0];
    const dueMonthsInput = Array.isArray(deliverable && deliverable.dueMonths)
      ? deliverable.dueMonths
      : fallback.dueMonths;

    return {
      id: sanitizeId(deliverable && deliverable.id, fallback.id),
      wpId: sanitizeId(deliverable && deliverable.wpId, fallback.wpId),
      title: asString(deliverable && deliverable.title, fallback.title),
      dueMonths: uniqueNumbers(dueMonthsInput),
      status: normalizeStatus(deliverable && deliverable.status),
      description: asString(deliverable && deliverable.description, fallback.description)
    };
  }

  function normalizeMilestone(milestone, index) {
    const fallback = DEFAULT_DATA.milestones[index] || DEFAULT_DATA.milestones[0];
    return {
      id: sanitizeId(milestone && milestone.id, fallback.id),
      title: asString(milestone && milestone.title, fallback.title),
      dueMonth: clampMonth(milestone && milestone.dueMonth, fallback.dueMonth),
      wpId: sanitizeId(milestone && milestone.wpId, fallback.wpId),
      verification: asString(milestone && milestone.verification, fallback.verification),
      status: normalizeStatus(milestone && milestone.status),
      description: asString(milestone && milestone.description, fallback.description)
    };
  }

  function findByIdOrIndex(items, id, index) {
    if (!Array.isArray(items)) {
      return null;
    }
    const found = items.find((item) => sanitizeId(item && item.id, "") === id);
    if (found) {
      return found;
    }
    return items[index] || null;
  }

  // Keep all proposal-derived structure immutable; only runtime status/description fields are editable.
  function normalizeData(input) {
    const base = clone(DEFAULT_DATA);
    if (!input || typeof input !== "object") {
      return base;
    }

    if (input.meta && typeof input.meta === "object") {
      base.meta.updatedAt = typeof input.meta.updatedAt === "string" ? input.meta.updatedAt : null;
    }

    for (let i = 0; i < base.wps.length; i += 1) {
      const fixed = base.wps[i];
      const fromInput = findByIdOrIndex(input.wps, fixed.id, i);
      if (!fromInput) {
        continue;
      }
      fixed.status = normalizeStatus(fromInput.status);
      fixed.description = asString(fromInput.description, fixed.description);
    }

    for (let i = 0; i < base.tasks.length; i += 1) {
      const fixed = base.tasks[i];
      const fromInput = findByIdOrIndex(input.tasks, fixed.id, i);
      if (!fromInput) {
        continue;
      }
      fixed.status = normalizeStatus(fromInput.status);
      fixed.description = asString(fromInput.description, fixed.description);
    }

    for (let i = 0; i < base.deliverables.length; i += 1) {
      const fixed = base.deliverables[i];
      const fromInput = findByIdOrIndex(input.deliverables, fixed.id, i);
      if (!fromInput) {
        continue;
      }
      fixed.status = normalizeStatus(fromInput.status);
      fixed.description = asString(fromInput.description, fixed.description);
    }

    for (let i = 0; i < base.milestones.length; i += 1) {
      const fixed = base.milestones[i];
      const fromInput = findByIdOrIndex(input.milestones, fixed.id, i);
      if (!fromInput) {
        continue;
      }
      fixed.status = normalizeStatus(fromInput.status);
      fixed.description = asString(fromInput.description, fixed.description);
    }

    return base;
  }

  function toTaskMap(tasks, wps) {
    const map = {};
    for (const wp of wps) {
      map[wp.id] = [];
    }
    for (const task of tasks) {
      if (!map[task.wpId]) {
        map[task.wpId] = [];
      }
      map[task.wpId].push({
        id: task.id,
        label: task.label,
        description: task.description,
        lead: task.lead,
        participants: task.participants,
        startMonth: task.startMonth,
        endMonth: task.endMonth,
        status: task.status
      });
    }

    for (const wpId of Object.keys(map)) {
      map[wpId].sort((a, b) => a.id.localeCompare(b.id, undefined, { numeric: true }));
    }

    return map;
  }

  function load() {
    try {
      const raw = global.localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        return { data: clone(DEFAULT_DATA), source: "default" };
      }
      const parsed = JSON.parse(raw);
      return { data: normalizeData(parsed), source: "local" };
    } catch (error) {
      console.warn("Failed to read local data, falling back to defaults", error);
      return { data: clone(DEFAULT_DATA), source: "default" };
    }
  }

  function save(data) {
    const normalized = normalizeData(data);
    normalized.meta.updatedAt = new Date().toISOString();
    global.localStorage.setItem(STORAGE_KEY, JSON.stringify(normalized));
    return clone(normalized);
  }

  function clear() {
    global.localStorage.removeItem(STORAGE_KEY);
  }

  function exportText(data) {
    return JSON.stringify(normalizeData(data), null, 2);
  }

  function importText(text) {
    const parsed = JSON.parse(text);
    return normalizeData(parsed);
  }

  global.RippleData = {
    STORAGE_KEY,
    STATUS_VALUES,
    DEFAULT_DATA: clone(DEFAULT_DATA),
    load,
    save,
    clear,
    normalizeData,
    toTaskMap,
    exportText,
    importText
  };
})(window);
