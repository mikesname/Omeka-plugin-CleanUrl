Clean Url (plugin for Omeka)
============================


Summary
-------

[Clean Url] is a plugin for [Omeka] allows to have clean, readable and search
engine optimized URLs like `http://example.com/my_collection/dc:identifier`
instead of `http://example.com/items/show/internal_code`.

See the example of the digitized heritage of the library of [Mines ParisTech].


Usage
-----

Simply set an identifier for each record in a field (`Dublin Core:Identifier` by
default).

* If not set, the identifier will be the default id of the record.
* A prefix can be added if you have other data in the same field.
* Multiple urls can be set, in particular to have a permalink and a search engine
optimized link.
* If multiple identifiers are set, the first one will be used to set the default
url, but other ones can be used to get the record.
* If the same identifier is used for multiple records, only the first record can
be got. Currently, not check is done on duplicate names.
* A main path can be added before collection names, as "collections" or "library":
`http://example.com/main_path/my_collection/dc:identifier`.
* A generic and persistent url for all items can be used too, for example
`http://example.com/document/dc:identifier`.
* Clean urls are automatically displayed in public theme and they are not used
in the admin theme.


Installation
------------

Uncompress files and rename plugin folder "CleanUrl".

Then install it like any other Omeka plugin and follow the config instructions.


Warning
-------

Use it at your own risk.

It's always recommended to backup your files and database regularly so you can
roll back if needed.


Troubleshooting
---------------

See online issues on the [Clean Url issues] page on GitHub.


License
-------

This plugin is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software's author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user's
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.


Contact
-------

Current maintainers:

* Daniel Berthereau (see [Daniel-KM] on GitHub)

First version of this plugin has been built for [École des Ponts ParisTech].
The upgrade for Omeka 2.0 has been built for [Mines ParisTech].


Copyright
---------

* Copyright Daniel Berthereau, 2012-2014

Based on ItemId of Jim Safley (see [GitHub ItemId])


[Clean Url]: https://github.com/Daniel-KM/CleanUrl
[Omeka]: http://www.omeka.org
[Clean Url issues]: https://github.com/Daniel-KM/CleanUrl/Issues
[CeCILL v2.1]: http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Daniel-KM]: http://github.com/Daniel-KM "Daniel Berthereau"
[École des Ponts ParisTech]: http://bibliotheque.enpc.fr
[Mines ParisTech]: https://patrimoine.mines-paristech.fr
[GitHub ItemId]: https://github.com/jimsafley/ItemId
