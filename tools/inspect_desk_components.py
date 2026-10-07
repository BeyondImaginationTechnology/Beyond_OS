import bpy
from collections import defaultdict, deque
o=bpy.data.objects['ChrisStudy_Desk_Root']; me=o.data
adj=defaultdict(list)
for p in me.polygons:
 vs=list(p.vertices)
 for i,v in enumerate(vs):
  adj[v].extend(vs[:i]+vs[i+1:])
seen=set(); comps=[]
for v in range(len(me.vertices)):
 if v in seen: continue
 q=[v];seen.add(v); c=[]
 while q:
  a=q.pop();c.append(a)
  for n in adj[a]:
   if n not in seen:seen.add(n);q.append(n)
 comps.append(c)
print('comps',len(comps),'verts',len(me.vertices))
for i,c in sorted(enumerate(comps),key=lambda x:len(x[1]),reverse=True)[:30]:
 coords=[me.vertices[v].co for v in c]
 mn=[min(v[j] for v in coords) for j in range(3)]; mx=[max(v[j] for v in coords) for j in range(3)]
 print(i,len(c),'min',tuple(round(x,2) for x in mn),'max',tuple(round(x,2) for x in mx),'ctr',tuple(round((mn[j]+mx[j])/2,2) for j in range(3)))
