import bpy
for n in ['DB_Study_Verse_Passage','DB_Study_Verse_Screen','DB_Study_Verse_Title','DB_Study_Verse_Reference','Chris_Morning_Wide_Camera']:
 o=bpy.data.objects.get(n)
 print(n, tuple(round(a,3) for a in o.location),tuple(round(a,3) for a in o.dimensions), 'body='+repr(o.data.body) if o.type=='FONT' else '')
