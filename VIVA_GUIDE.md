# Viva Defence Guide

This document is your cheat sheet for the project viva. Read it the night before.

## 🎬 The Three Wow Moments

Your demo flow should hit these three moments. They're orchestrated to show progressively more intelligence.

### Wow #1: Live Track Classification (1 minute)

**What to do:**
1. Go to "Register New Case"
2. Select "Bail Application" as case type — pause and point at the right panel: "Notice the track was instantly assigned as **Fast Track** because bail is time-sensitive"
3. Switch the case type to "Civil Suit" — "Now it's Standard"
4. Switch back to a non-bail criminal case (e.g. Theft), then tick "In Custody" on the respondent — "And now it's flipped to Fast Track because the accused is in custody — a constitutional liberty concern overriding the case type's default"

**What you're proving:** The system makes rule-based decisions that consider multiple inputs, with reasoning the user can audit.

**Likely question:** *Why not use machine learning here?*
**Your answer:** "Judicial decisions need to be explainable. A rule-based system can defend every choice in front of a judge. ML would be a black box. We can revisit with ML once we have years of labelled data — I've noted it as future scope."

---

### Wow #2: Priority Score Breakdown (2 minutes)

**What to do:**
1. Open any case from the seeded data — ideally one with adjournments and age
2. Point at the **dark right-hand card** showing the score breakdown
3. Walk through: "The base priority is 50 from the case type. Age contributed 12 points because it's 8 months old. Adjournments contributed 8 because there have been 5 adjournments. Stage contributed 7 because we're in evidence stage. Total: 77."
4. Click "Compute now" to show the score recomputes live

**What you're proving:** The algorithm is transparent, weighted, and produces explainable outputs.

**Likely question:** *How did you decide the weights?*
**Your answer:** "I started with the principle that age and adjournments should matter most because they're the visible signs of justice delayed. Urgency factors (interim relief, custody) come next because they signal external pressure. Stage gets a moderate weight to bump cases near disposal. I made all weights constants at the top of the service class so they can be tuned without changing logic, and I tracked score history in a separate table so we can analyse whether the weights work over time."

---

### Wow #3: Intelligent Cause List Generation (2 minutes)

**What to do:**
1. Go to "Generate Cause List" → pick a judge → tomorrow's date → Generate
2. Show the resulting list — point out the ordering: "Fast track cases are at the top, sorted by priority within. Standard cases come next. Complex at the bottom."
3. Open one fast-track case and one standard-track case from the list. Show that **the fast-track one has a lower priority score than the standard one**, but it's still listed first — because track precedence beats raw score.
4. Show the conflict warning if any appears (the seeder has been designed to occasionally produce one). "The system flagged that lawyer X has hearings in two courts the same day."
5. Download the PDF and show how it'd be printed and posted on the courtroom door.

**What you're proving:** The system makes actually useful operational decisions, not just stores data.

**Likely question:** *What happens if two cases have the same priority score?*
**Your answer:** "Within a track, ties are broken by age — older cases come first. This is implicit in the sort because age contributes to the score, so a true tie is extremely rare. But the system would still produce a deterministic order — it's stable."

---

## 🎓 Architectural Defences

**Q: Why Laravel?**
Mature ecosystem with built-in auth, queues, scheduler, mail, ORM. Reduces boilerplate so I could focus on the DCFM logic rather than wiring infrastructure.

**Q: Why service classes instead of putting logic in models?**
Single responsibility. The Case model represents data; `TrackClassifier` represents the classification algorithm; `PriorityScorer` represents scoring. This makes the DCFM rules testable in isolation and swappable later (e.g., I could replace the rule-based scorer with an ML version without touching the case model).

**Q: Why a separate `priority_score_history` table?**
For two reasons. First, audit — judicial systems need to know why a case was listed when it was. Second, analytics — I can track how scores evolved and whether the weights work in practice.

**Q: Why use snapshots in `cause_list_items` instead of joining cases live?**
Because a cause list is a historical record. Once a list is generated and published, the case priority might change tomorrow, but the listing for today should remain as it was. Snapshotting `priority_score_at_listing` and `track_at_listing` preserves the audit trail.

**Q: How does the system handle concurrent case registrations?**
The `CaseNumberGenerator` uses a transaction with `lockForUpdate` to prevent race conditions on the case number sequence.

**Q: What about scalability?**
The bottleneck would be the daily rescoring job. I chunked the query (`->chunk(100, ...)`) so memory stays bounded. For larger deployments, the job could be sharded by court — each court rescores independently.

**Q: How would real courts adopt this?**
Phase 1 is the registry plus this listing engine. Phase 2 would add e-filing with digital signatures (DSC integration), video hearing scheduling, and integration with the existing eCourts API. Phase 3 could add ML-based prediction. I noted these as future scope.

---

## 🐛 Likely Bug Questions

**Q: What if a judge becomes unavailable after the cause list is generated?**
The list status stays as draft until published. If the judge is marked unavailable, the morning staff would regenerate the list — the generator throws a `RuntimeException` if you try to generate for an unavailable judge.

**Q: What if a case has no priority score yet?**
The case is created with `priority_score = 0` and immediately scored by `CaseRegistrationService`. The cause list generator orders by score descending, so unscored cases would appear last — but in practice, registration always triggers scoring.

**Q: What about cases without a `next_hearing_date`?**
The CauseListGenerator picks them up as "gap fillers" — active cases whose next_hearing_date is null or in the past, ranked by priority. This handles fresh filings that need their first listing.

---

## 🧪 Test Coverage Talking Points

If the evaluator asks "did you write tests?":

"Yes — the core DCFM algorithms have unit tests. I have 6 tests for `PriorityScorer` covering: bail vs civil scoring, age impact, adjournment impact, in-custody impact, score boundedness in [0,100], and factor-breakdown structure. Another 6 for `TrackClassifier` covering each classification rule. Run with `php artisan test`."

If they want to see one, open `tests/Unit/PriorityScorerTest.php` and walk through `test_bail_case_scores_higher_than_civil_case_of_same_age` — it's the cleanest demonstration of the algorithm's correctness.

---

## 📊 Numbers to Have Ready

Memorize these for impact:

- **22** case types seeded across 5 categories (civil, criminal, family, commercial, constitutional)
- **3** tracks (fast / standard / complex) with **5** weighted factors in the scoring algorithm
- **6** service classes, **12** Eloquent models, **11** migrations
- **12** unit tests covering the core DCFM logic
- **~30** demo cases with realistic priority spread across all tracks
- **5** user roles with **17** distinct permissions

---

## 🎤 Closing Statement (rehearse this)

"In summary, this project digitizes the Differentiated Case Flow Management framework — a real judicial reform initiative — and adds an intelligent priority scoring layer on top. The system can register cases, classify them into tracks, automatically generate ordered daily cause lists, detect conflicts, and predict at-risk cases. The architecture separates business logic into testable service classes, preserves audit trails for judicial transparency, and is built on a stack that production courts could realistically deploy. I see this as a working prototype that demonstrates how technology can address the systemic backlog problem in Indian courts."

Good luck. You've got this.
