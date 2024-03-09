import{c,r,u as A,a as P,b,j as s,F as x,L as C,A as p,d as v,T as a,I as u,e as k,f as y,g as E,h as D,i as F,k as R,N as t,l as H,m as U,n as B,o as N,S as M,p as O,O as I,q as G,s as f}from"./index-4V34fw3k.js";var K=c("adjustments-horizontal","IconAdjustmentsHorizontal",[["path",{d:"M14 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0",key:"svg-0"}],["path",{d:"M4 6l8 0",key:"svg-1"}],["path",{d:"M16 6l4 0",key:"svg-2"}],["path",{d:"M8 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0",key:"svg-3"}],["path",{d:"M4 12l2 0",key:"svg-4"}],["path",{d:"M10 12l10 0",key:"svg-5"}],["path",{d:"M17 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0",key:"svg-6"}],["path",{d:"M4 18l11 0",key:"svg-7"}],["path",{d:"M19 18l1 0",key:"svg-8"}]]),T=c("files","IconFiles",[["path",{d:"M15 3v4a1 1 0 0 0 1 1h4",key:"svg-0"}],["path",{d:"M18 17h-7a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h4l5 5v7a2 2 0 0 1 -2 2z",key:"svg-1"}],["path",{d:"M16 17v2a2 2 0 0 1 -2 2h-7a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h2",key:"svg-2"}]]),V=c("key","IconKey",[["path",{d:"M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z",key:"svg-0"}],["path",{d:"M15 9h.01",key:"svg-1"}]]),q=c("packages","IconPackages",[["path",{d:"M7 16.5l-5 -3l5 -3l5 3v5.5l-5 3z",key:"svg-0"}],["path",{d:"M2 13.5v5.5l5 3",key:"svg-1"}],["path",{d:"M7 16.545l5 -3.03",key:"svg-2"}],["path",{d:"M17 16.5l-5 -3l5 -3l5 3v5.5l-5 3z",key:"svg-3"}],["path",{d:"M12 19l5 3",key:"svg-4"}],["path",{d:"M17 16.5l5 -3",key:"svg-5"}],["path",{d:"M12 13.5v-5.5l-5 -3l5 -3l5 3v5.5",key:"svg-6"}],["path",{d:"M7 5.03v5.455",key:"svg-7"}],["path",{d:"M12 8l5 -3",key:"svg-8"}]]),X=c("users","IconUsers",[["path",{d:"M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0",key:"svg-0"}],["path",{d:"M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2",key:"svg-1"}],["path",{d:"M16 3.13a4 4 0 0 1 0 7.75",key:"svg-2"}],["path",{d:"M21 21v-2a4 4 0 0 0 -3 -3.85",key:"svg-3"}]]);const _=r.memo(function({sidebarStatus:e,toggleSidebar:o,onClickBars:n}){const{isDemo:l}=A(),{isLoading:w,settings:L}=P(),{user:h}=b(),[d,j]=r.useState(!1),g=r.useRef();let z=e?y:E,S=d?y:D;return r.useEffect(()=>{e&&d&&j(!1)},[e]),r.useEffect(()=>{w||(d&&e&&o(!1),d?g.current.classList.add("show"):g.current.classList.remove("show"))},[d]),s.jsxs(s.Fragment,{children:[s.jsx("span",{className:"bars",onClick:m=>n(m),children:s.jsx(x,{icon:z})}),s.jsx("div",{className:"dashboard-brand",children:s.jsx(C,{settings:L,isDemo:l})}),s.jsx("span",{className:"dots",onClick:()=>j(m=>!m),children:s.jsx(x,{icon:S})}),s.jsxs("nav",{className:"right-nav",ref:g,children:[s.jsxs("div",{className:"d-md-none d-flex flex-column align-items-center mb-4",children:[s.jsx(p,{username:h.username,size:100})," Hi, ",h.username]}),s.jsxs("div",{className:"d-md-none",children:[s.jsxs(v,{to:"/account/settings",children:[s.jsx(a,{icon:u})," Profile"]}),s.jsxs(v,{to:"/logout",children:[s.jsx(a,{icon:k})," Logout"]})]})]}),s.jsxs("div",{className:"btn-group avatar-dropdown",children:[s.jsxs("button",{className:"btn btn-primary dropdown-toggle d-flex align-items-center gap-2","data-bs-toggle":"dropdown","data-bs-display":"static","aria-expanded":"false",children:[s.jsx(p,{username:h.username,size:45})," Hi, ",h.username]}),s.jsxs("div",{className:"dropdown-menu dropdown-menu-lg-end dropdown-menu-dark",children:[s.jsxs(v,{to:"/account/settings",className:"dropdown-item",children:[s.jsx(a,{icon:u})," Profile"]}),s.jsx("hr",{className:"dropdown-divider"}),s.jsxs(v,{to:"/logout",className:"dropdown-item",children:[s.jsx(a,{icon:k})," Logout"]})]})]})]})}),$=r.memo(function({show:e}){const{isExtendedLicense:o}=F(),{settings:n}=R();let l="dashboard-sidebar";return e&&(l+=" show"),s.jsxs("aside",{className:`${l} d-flex flex-column justify-content-between`,children:[s.jsxs("nav",{children:[s.jsxs(t,{to:"",end:!0,children:[s.jsx(a,{icon:H,stroke:1.25,size:30})," Dashboard"]}),s.jsxs(t,{to:"customers",children:[s.jsx(a,{icon:X,stroke:1.25,size:30})," Customers"]}),o&&s.jsxs(s.Fragment,{children:[s.jsxs(t,{to:"plans",children:[s.jsx(a,{icon:q,stroke:1.25,size:30})," Plans"]}),s.jsxs(t,{to:"subscriptions",children:[s.jsx(x,{icon:U,size:"lg"})," Subscriptions"]}),s.jsxs(t,{to:"payment-gateways",children:[s.jsx(a,{icon:B,stroke:1.25,size:30})," Payment Gateways"]})]}),s.jsxs(t,{to:"pages",children:[s.jsx(a,{icon:T,stroke:1.25,size:30})," Pages"]}),s.jsxs(t,{to:"api-keys",children:[s.jsx(a,{icon:V,stroke:1.25,size:30})," API keys"]}),s.jsxs(t,{to:"settings",children:[s.jsx(a,{icon:K,stroke:1.25,size:30})," Settings"]})]}),s.jsxs("div",{className:"text-center py-3",children:["v",n==null?void 0:n.APP_VERSION]})]})});function J(){const[i,e]=r.useState(!1),o=N();return s.jsxs("div",{className:"dashboard",children:[s.jsx("header",{className:"dashboard-header",children:s.jsx(_,{sidebarStatus:i,toggleSidebar:e,onClickBars:()=>e(n=>!n)})}),s.jsx($,{show:i}),s.jsxs("main",{className:"dashboard-container",children:[s.jsx(M,{children:s.jsx(O,{children:s.jsx("div",{className:"dashboard-content",children:s.jsx(I,{})})})},o.pathname),s.jsx("footer",{children:"© All rights reserved."})]})]})}function W(){const i=N(),{isActive:e}=G(),{isAuthenticated:o,isAdmin:n}=b();return e?!o||!n?s.jsx(f,{to:"/login",replace:!0}):s.jsx(M,{children:s.jsx(J,{children:s.jsx(I,{})})},i.pathname):s.jsx(f,{to:"/admin/license"})}export{W as default};