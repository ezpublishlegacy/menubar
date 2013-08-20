{* Menubar Template *}
{if $menubar.item_count}
{if $menubar.has_header}
	<h2{if $menubar.header.class} class="{$menubar.header.class}"{/if}>{if $menubar.header.link}<a href="{$menubar.header.link}">{/if}{$menubar.header.content}{if $menubar.header.link}</a>{/if}</h2>
{/if}
{if $menubar.columnize}<div class="column">{/if}
<ul class="menu {$menubar.class}">
	{foreach $menubar.items as $key=>$item}
{if and($menubar.is_multiple, is_set($menubar.split_points[$key]))}
</ul>
{if $menubar.columnize}
</div>
<div class="column">
{/if}
{if and(is_set($menubar.split_points[$key]), $menubar.split_points[$key].has_content)}<h3>{$menubar.split_points[$key].content}</h3>{elseif not($menubar.columnize)}{include uri="design:content/datatype/view/ezxmltags/separator.tpl"}{/if}
<ul class="menu {$menubar.class}">
{/if}
	<li{if $item.class} class="{$item.class}"{/if}>
		{if $item.link}<a href="{$item.link}"{if $item.is_external} class="external" rel="external"{/if}{if $item.target} target="{$item.target}"{/if}>{else}<span class="inactive">{/if}{$item.content}{if $item.link}</a>{else}</span>{/if}{if $item.delimiter}<span class="delimiter">{$item.delimiter}</span>{/if}
		{if $item.menu}{$item.menu|menubar()}{/if}
	</li>
	{/foreach}
</ul>
{if $menubar.columnize}</div>{/if}
{/if}