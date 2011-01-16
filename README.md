# GIS (Geospatial Information System) Module #

## Introduction

Adds GIS (Geographic Information Systems) capabilities to Silverstripe, 
in the form of new database types, formfields and mapping UI functionality.

The module was developed on MySQL5, which contains rudimentary
support for [OGC](http://www.opengeospatial.org/) data types.
It has not been tested with PostgreSQL, PostGIS or similiar (more advanced) database drivers.

Feature Overview:

 * Tile rendering with GD image processing library
 * Tile queuing via custom database-backed queue implementation
 * Shapefile conversion into Dataobject properties (via thirdparty librayr)
 * GIS-specific DBField subclasses: GeoPoint, GeoPolygon, GeoLineString
 * GIS-specific FormField subclasses: GeoPointField, GeocoderField (Geocode an address-string to a set of coordinates using Google's free geocoding services)
 * GIS-specific SearchFilter subclasses: BoundsFilter, LatLngBoundsFilter
 * KMLDataFormatter for KLM output from the RestfulServer class on any DataObject
 * CSVDataFormatter (not really GIS specific) 

## Maintainer

 * Ingo Schommer (Nickname: ischommer, chillu)
   <ingo (at) silverstripe (dot) com>

## Requirements

 * SilverStripe 2.3.x, not tested with SilverStripe 2.4.x

## Installation

Copy the modules directory into your SilverStripe webroot

## Usage ##

The first practical usage of this module is the [New Zealand National Broadbandmap](http://broadbandmap.govt.nz).
This SilverStripe project is opensource itself, we highly recommend to review its [source code](http://broadbandmap.govt.nz/source-code/)
to get a better understanding what the GIS module does.

## Known issues ##

 * The thirdparty shapefile library is inefficient and doesn't support MULTIPOLYGON records
 * Doesn't support tile serving (via .htaccess rewrite rules)
 * The underlying MySQL support for OGC standard data formats and SQL syntax is sketchy at best