# Spooler

The role of the spooler is to send emails in a controlled way. It can handle multiples queues 
at the same time and recover from errors or crashes. It's also mutli-process safe so you can 
have multiple spoolers running at the same time on the same queue.

## Max execution time

Emails are twig templates, so user controlled logic will have to be executed when sending the email.

In this context, anything can happen. The template can be malformed, can crash, or worse can contain
an infinite loop.

As a protection against these problems, the spooler is designed so it can crash anytime and recover from it later.

It also enforce a maximum execution time partially controlled in the configuration.

When you set the `max_execution_time` parameter, you indicate how much time the `flush` operation can
run. Time time limit of php will be slightly superior to ensure the spooler is not killed in a middle of normal operations.

When flushing, the spooler checks if it has time to send another mail before reaching the time limit.
If it don't, it stops by itself.

If an infinite loop occurs, php will kill the script when the time limit is reached.
