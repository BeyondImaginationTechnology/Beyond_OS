# Jaguar user-submitted training suggestions

Jaguar chat does not save whole conversations. A user can optionally choose **Suggest an improvement** under an assistant answer, enter a correction, select `Jaguar general` or `Beyond Tattoo stencil editor`, and explicitly consent to storing that one prompt/answer/correction pair for human review.

Submissions are rate limited, stored in `jaguar_feedback_submissions` with `pending` status, and are not sent to the inference runtime. Administrators review them at `ai/admin/training-feedback.php`; approve, reject, or delete each entry. Approved entries can be exported as JSONL filtered to one scope. Export is a dataset step only: it does not fine-tune or change the live model. Keep Beyond Tattoo entries in their own scope and review accuracy, personal information, permissions, and product fit before any later training run.

The review console stores a user ID for signed-in submissions and does not silently ingest other messages in the conversation. Guest submissions are rate limited using a one-way IP hash in the existing rate-limit mechanism; the feedback record itself contains no IP address. Deletion from the queue removes the submission from this database. The browser copy asks users to remove sensitive details before submitting.
