# Tracking must be committed before a message becomes sendable

`Spooler::scheduleMessage()` writes the complete serialized message to a unique
`.tmp` file in the destination queue directory. Queue listeners then run;
`MessageTrackerListener::onQueue()` forces persistence of the queued tracking
entity and performs a strict flush. Only after all listeners return successfully
is the temporary file renamed to `.message.rN`. The consumer never selects `.tmp`
files. The rename stays within the same directory.

This prevents the consumer from sending a newly queued tracked message before its
tracking row exists. It also prevents consumption of a partially written message.
`spooler:queue` listeners now run before the final queue file is visible; they must
not assume the final file exists. Initial persistence of caller-managed tracking
entities is forced at this boundary, so any required relations must be persistable.

An open application transaction is rejected for tracked messages. A flush within
that transaction would not make the row visible to another database connection.
Queue the message after committing the business transaction; the bundle does not
commit or roll back that transaction on the caller's behalf.

A write, tracking or publication failure removes the temporary file, emits the
failure/abandon events and is not counted as a successful enqueue. Failure state
updates remain pending for the normal tracker flush if persistence is available.
`send()` / `schedule()` return the number of messages actually published, which may
be smaller than the number submitted. Callers must check that return value.
The normal SMTP retries and maximum-attempt handling remain in place.

`MessageTracker::flush(true)` propagates persistence errors. Default `flush()`
keeps reporting errors through the existing error tracker. Failed batches stay
pending rather than being silently discarded; an ORM closed by a failed flush
still requires recovery by its owner.

This patch does not guarantee exactly-once SMTP delivery: a process can still
stop after SMTP acceptance and before saving the send result. It does not repair
historical tracking rows or change any application recovery cron.
