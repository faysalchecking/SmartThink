## SmartThink কী

SmartThink হলো একটি web-based **ATS (Applicant Tracking System)**, অর্থাৎ recruitment কোম্পানিগুলোর জন্য candidate, job, document ও CV ব্যবস্থাপনার সফটওয়্যার। একটি installation-এ অনেক কোম্পানি আলাদাভাবে কাজ করতে পারবে, এবং একটি কোম্পানির তথ্য অন্য কোম্পানি কোনোভাবেই দেখতে, বদলাতে বা download করতে পারবে না। এটিকে বলে **multi-tenant SaaS**।

এটি বিশেষভাবে সাহায্য করবে ছোট ও মাঝারি recruitment agency-কে, বিশেষ করে বিদেশে কর্মী পাঠানোর (overseas manpower) agency-কে, যারা এখন Excel, WhatsApp ও কাগজে কাজ করে।

## কারা এটি ব্যবহার করবে

| ভূমিকা | কী করতে পারবে | কী করতে পারবে না |
|---|---|---|
| **Platform Super Admin** (আপনি বা সফটওয়্যারের মালিক) | নতুন কোম্পানি তৈরি, চালু/বন্ধ করা | Company-র candidate বা document দেখা (সাধারণভাবে) |
| **Company Admin** (কোম্পানির প্রধান) | কোম্পানির সেটিংস, ব্যবহারকারী আমন্ত্রণ, role ও permission ঠিক করা, pipeline stage সাজানো | অন্য কোম্পানির কিছু দেখা |
| **Employee / Sub-admin** (কর্মী) | Admin-এর দেওয়া permission অনুযায়ী candidate, job, document, CV নিয়ে কাজ | Permission ছাড়া export, delete বা role বদলানো |

একজন ব্যবহারকারী একাধিক কোম্পানিতে সদস্য হতে পারবে, কিন্তু প্রতিটিতে তার role আলাদা হতে পারে।

## সফটওয়্যার যা যা করবে

**১. নিরাপদ login ও কোম্পানি ব্যবস্থাপনা**
- Email ও password দিয়ে login। Password এনক্রিপ্টেড (hash) অবস্থায় থাকে।
- বারবার ভুল password দিলে সাময়িকভাবে আটকে যাবে (rate limit)।
- Admin ব্যবহারকারীকে email দিয়ে আমন্ত্রণ পাঠাবে।
- কর্মী বা কোম্পানি নিষ্ক্রিয় করা যাবে, তখন তার সব প্রবেশাধিকার বন্ধ হবে।

**২. Candidate ব্যবস্থাপনা**
- Candidate-এর তথ্য যোগ, সম্পাদনা ও archive (মুছে না, লুকানো থাকে)।
- Search, filter ও পৃষ্ঠা ভাগ (pagination) করে বড় তালিকা দেখা।
- প্রতিটি candidate-এর সম্পূর্ণ activity history: কখন কে কী বদলেছে।

**৩. Client ও Job Order**
- Employer বা client-এর তথ্য রাখা।
- Job order তৈরি, যেটি একটি client-এর সাথে যুক্ত থাকবে। Status: draft, open, on hold, closed।

**৪. Recruitment pipeline ও overseas stage**
- Candidate একটি job-এর জন্য একটি stage থেকে পরের stage-এ যাবে।
- প্রতিটি কোম্পানি নিজের stage সাজাতে পারবে। যেমন: Screening → Interview → Selected → Passport check → Medical → Visa → Embassy → Deployment।
- Overseas stage (medical, visa, embassy, deployment) বাধ্যতামূলক নয়, কোম্পানি চাইলে চালু বা বন্ধ করবে।
- Stage বদলানোর ইতিহাস রাখা হবে এবং মুছে ফেলা যাবে না।

**৫. Interview** (পরিকল্পিত, অগ্রাধিকার মধ্যম)
- Interview সময়সূচি, interviewer ও ফলাফল রাখা।

**৬. Document ব্যবস্থাপনা**
- Candidate-এর passport, medical, certificate ইত্যাদি upload।
- শুধু PDF, JPG, PNG অনুমোদিত। ফাইলের আসল ধরন যাচাই করা হবে, শুধু extension দেখে নয়।
- প্রতিটি document-এর অবস্থা: pending, verified, rejected।
- Document কখনো public URL-এ থাকবে না। শুধু যথাযথ অনুমতি থাকলে download হবে।

**৭. CV Builder (এই সফটওয়্যারের বিশেষ অংশ)**
- Candidate-এর যাচাইকৃত তথ্য থেকে formal ও print-ready CV তৈরি।
- কমপক্ষে ৩টি পেশাদার template, এবং template বদলালে একই তথ্য নতুনভাবে সাজবে।
- Section এর ক্রম বদলানো, optional section লুকানো, দীর্ঘ লেখা বা বিশেষ অক্ষর সামলানো।
- তথ্য না থাকলে সেটি "missing" দেখাবে, কোনো কিছু বানানো হবে না।
- PDF export-এর আগে একজন মানুষকে review করে confirm করতে হবে।
- এটি নিজে থেকে লেখে না। AI ব্যবহার সম্পূর্ণ optional, এবং ডিফল্টভাবে বন্ধ।

**৮. Dashboard ও report**
- কোম্পানির নিজের তথ্য থেকে গণনা: কতজন candidate কোন stage-এ, কোন job-এ কতজন।
- Pipeline report (পরের দিকে)।

**৯. Export ও audit**
- Candidate তালিকা CSV-তে export, শুধু যাদের অনুমতি আছে।
- কে login করল, কে কী বদলাল, কে export বা document দেখল, সব audit log-এ লেখা থাকবে। Audit log মুছে বা বদলানো যাবে না।

**১০. নিরাপত্তা ও ব্যাকআপ**
- এক কোম্পানির ব্যবহারকারী অন্য কোম্পানির কোনো তথ্য দেখতে, বদলাতে, খুঁজতে বা download করতে পারবে না। এটি সার্ভারে বারবার test করে প্রমাণ করা হবে।
- Database ও document-এর ব্যাকআপ, এবং restore-এর test।

## কোন phase-এ কী তৈরি হবে

| Phase | কাজ | শেষ হলে যা চালু থাকবে |
|---|---|---|
| **0** | পরিকল্পনা, documentation, architecture, roadmap | সব সিদ্ধান্ত লেখা, আপনি review করে confirm করেছেন |
| **1** | Foundation: project structure, React ও Fastify-এর খালি কাঠামো, database migration, lint ও test setup, একটি setup script | কোনো business feature নেই। শুধু `npm run check` চললে সফল, এবং নতুন PC-তে একই setup কাজ করে |
| **2** | Login, session, কোম্পানি তৈরি ও সক্রিয়করণ, সদস্য আমন্ত্রণ, role ও permission, audit log, এবং tenant isolation test | Login করা যাবে, এক কোম্পানির কেউ অন্যটির কিছু দেখতে পারবে না (test দিয়ে প্রমাণিত) |
| **3** | Client, job order, candidate, configurable pipeline ও overseas stage, activity history, search, pagination, dashboard | Recruitment-এর মূল কাজগুলো চালু, interview সহ (অগ্রাধিকার অনুযায়ী) |
| **4** | Document upload/download ও review, CV Builder (৩+ template), PDF export, PDF test | Candidate-এর document রাখা ও প্রফেশনাল CV PDF বানানো |
| **5** | CSV export (অনুমতি ও audit সহ), pipeline report, notification ও অন্যান্য S/C item | Reporting ও export সম্পূর্ণ |
| **6** | নিরাপত্তা শক্তিশালী করা, dependency audit, বড় ডেটায় performance, backup ও restore test | Restore সফল হলে লগসহ প্রমাণিত |
| **7** | Deployment guide, server setup, user guide, operations runbook, আলাদা মেশিনে clean install test | সফটওয়্যারটি release-এর জন্য প্রস্তুত |

প্রতিটি phase-এর শেষে আমি বলব কী সম্পন্ন হয়েছে, কোন test কতটা পাস হয়েছে, কী বাকি আছে। আপনি confirm না করা পর্যন্ত পরের phase শুরু হবে না।

## এই সফটওয়্যার যা এখনই করবে না

- Mobile app (শুধু web)
- Online payment বা subscription billing
- Public job board বা candidate নিজে প্রোফাইল দেওয়ার portal
- AI দিয়ে CV লেখা বা document পড়া (পরে optional হিসেবে যোগ হতে পারে)
- বাংলা UI (কাঠামো তৈরি থাকবে, অনুবাদ পরে)

## একটি উদাহরণ: একজন candidate-এর যাত্রা

১. Agency-র কর্মী একজন candidate-এর তথ্য যোগ করল।
২. "Saudi Arabia Driver" job-এ candidate-কে যুক্ত করল। Stage: Screening।
৩. Passport-এর scan upload করল। System ফাইলের ধরন যাচাই করল, এবং Admin দেখে "verified" দিল।
৪. Interview-র পর stage হলো Selected, তারপর Medical।
৫. Candidate-এর CV Builder-এ গিয়ে template বেছে নিল। তথ্য না থাকা অংশ "missing" দেখাল।
৬. কর্মী review করে confirm করার পর PDF তৈরি হলো। সেই মুহূর্তে audit log-এ লেখা হলো কে কখন export করল।
৭. অন্য কোম্পানির কেউ এই candidate-এর ID বসিয়ে খুলতে গেলে "পাওয়া যায়নি" দেখাবে।

## এখন আমরা কোথায়

- **Phase 0** প্রায় শেষ। বাকি আছে `API_SPEC`, `SYSTEM_MAP`, `WORKFLOW`, `INSTALLATION`, `DEPLOYMENT`, `USER_GUIDE`, `OPERATIONS` এবং `PHASE_STATUS`-এর চূড়ান্ত আপডেট।
- Phase 1 এখনো শুরু হয়নি।
- আপনার দুটি কাজ বাকি: GitHub token revoke করা (যেটি চ্যাটে এসেছিল) এবং আগের `git log` ও `git ls-remote` output পাঠানো।

একটি সৎ কথা: পুরো scope একজন developer-এর জন্য ৩০ দিনে bug-free ও পরীক্ষিত করা কঠিন। আমার হিসাবে এটি প্রায় ৬-৮ সপ্তাহ লাগতে পারে। প্রতিটি phase শেষে বাস্তব সময় মেপে আপনাকে জানাব, যাতে আপনি সিদ্ধান্ত নিতে পারেন।

পরের ধাপ হিসেবে আমি Phase 0-এর বাকি documentation দিতে পারি, অথবা আপনি যদি এই পরিকল্পনা মেনে নেন, তাহলে Phase 0 শেষ করার লিখিত confirmation দিন।