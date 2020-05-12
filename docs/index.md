---
layout: default
title: Home
nav_order: 1
description: "Database"
---

# Documentation

## Introduction

The database query builder provides a convenient, fluent interface for creating and executing database queries.
It can be used to perform most database operations in your application and works on all supported database systems (MySql).

The query builder uses quoting to protect your application against SQL injection attacks.

## Installation

Install the component with Composer:

```shell
composer require selective/database
```

## What's next

* Read about how to create an [Connection](connection.md) object to build queries with it.

## Framework integration

* [Slim 4](slim.md)

**Next page:** [Connection](connection.md)
